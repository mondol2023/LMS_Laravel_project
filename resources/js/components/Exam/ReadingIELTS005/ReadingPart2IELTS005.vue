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

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
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

const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

const { highlights, selectedText, selectionRange, colors, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

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

// ── Drag-and-drop for Q19-25 ─────────────────────────────────────────────────
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const wordOptions = [
    { value: 'A', label: 'emotional' },
    { value: 'B', label: 'negative' },
    { value: 'C', label: 'expensive' },
    { value: 'D', label: 'silent' },
    { value: 'E', label: 'social' },
    { value: 'F', label: 'outstanding' },
    { value: 'G', label: 'little' },
    { value: 'H', label: 'powerful' },
    { value: 'I', label: 'realistic' },
    { value: 'J', label: 'stylistic' },
    { value: 'K', label: 'economic' },
];

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

const getWordLabel = (value: string): string => {
    const option = wordOptions.find((o) => o.value === value);
    return option ? `${option.value}. ${option.label}` : '';
};

// Q19-25 drop zone label (shows letter + word)
const getDropLabel = (qKey: keyof typeof answers.value): string => {
    const val = answers.value[qKey];
    if (!val) return '';
    return getWordLabel(val);
};

// ── Passage text ─────────────────────────────────────────────────────────────
const passageText = `There has always been a sense in which America and Europe owned film. They invented it at the end of the nineteenth century in unfashionable places like New Jersey, Leeds and the suburbs of Lyons. At first, they saw their clumsy new camera-projectors merely as more profitable versions of Victorian lantern shows, mechanical curiosities which might have a use as a sideshow at a funfair. Then the best of the pioneers looked beyond the fairground properties of their invention. A few directors, now mostly forgotten, saw that the flickering new medium was more than just a diversion. This crass commercial invention gradually began to evolve as an art. D W Griffith in California glimpsed its grace, German directors used it as an analogue to the human mind and the modernising city, Soviets emphasised its agitational and intellectual properties, and the Italians reconfigured it on an operatic scale.

So heady were these first decades of cinema that America and Europe can be forgiven for assuming that they were the only game in town. In less than twenty years western cinema had grown out of all recognition; its unknowns became the most famous people in the world; it made millions. It never occurred to its financial backers that another continent might borrow their magic box and make it its own. But film industries were emerging in Shanghai, Bombay and Tokyo, some of which would outgrow those in the west.

Between 1930 and 1935, China produced more than 500 films, mostly conventionally made in studios in Shanghai, without soundtracks. China's best directors - Bu Wancang and Yuan Muzhi - introduced elements of realism to their stories. The Peach Girl (1931) and Street Angel (1937) are regularly voted among the best ever made in the country.

India followed a different course. In the west, the arrival of talkies gave birth to a new genre - the musical - but in India, every one of the 5000 films made between 1931 and the mid-1950s had musical interludes. The films were stylistically more wide ranging than the western musical, encompassing realism and escapist dance within individual sequences, and they were often three hours long rather than Hollywood's 90 minutes. The cost of such productions resulted in a distinctive national style of cinema. They were often made in Bombay, the centre of what is now known as 'Bollywood'. Performed in Hindi (rather than any of the numerous regional languages), they addressed social and peasant themes in an optimistic and romantic way and found markets in the Middle East, Africa and the Soviet Union.

In Japan, the film industry did not rival India's in size but was unusual in other ways. Whereas in Hollywood the producer was the central figure, in Tokyo the director chose the stories and hired the producer and actors. The model was that of an artist and his studio of apprentices. Employed by a studio as an assistant, a future director worked with senior figures, learned his craft, gained authority, until promoted to director with the power to select screenplays and performers. In the 1930s and 40s, this freedom of the director led to the production of some of Asia's finest films.

The films of Kenji Mizoguchi were among the greatest of these. Mizoguchi's films were usually set in the nineteenth century and analysed the way in which the lives of the female characters whom he chose as his focus were constrained by the society of the time. From Osaka Elegy (1936) to Ugetsu Monogatari (1953) and beyond, he evolved a sinuous way of moving his camera in and around a scene, advancing towards significant details but often retreating at moments of confrontation or strong feeling. No one had used the camera with such finesse before.

Even more important for film history, however, is the work of the great Ozu. Where Hollywood cranked up drama, Ozu avoided it. His camera seldom moved. It nestled at seated height, framing people square on, listening quietly to their words. Ozu rejected the conventions of editing, cutting not on action, as is usually done in the west, but for visual balance. Even more strikingly, Ozu regularly cuts away from his action to a shot of a tree or a kettle or clouds, not to establish a new location but as a moment of repose. Many historians now compare such 'pillow shots' to the Buddhist idea that mu- empty space or nothing - is itself an element of the composition.

As the art form most swayed by money and market, cinema would appear to be too busy to bother with questions of philosophy. The Asian nations proved and are still proving that this is not the case. Just as deep ideas about individual freedom have led to the aspirational cinema of Hollywood, so it is the beliefs which underlie cultures such as those of China and Japan that explain the distinctiveness of Asian cinema at its best. Yes, these films are visually striking, but it is their different sense of what a person is, and what space and action are, which makes them new to the western eye.`;

// ── Text segments (offsets recalculated dynamically below) ───────────────────
const allSegments = [
    // Part header
    { id: 'part-header',       text: 'Part 2' },
    { id: 'part-instructions', text: 'Read the text and answer questions 14-26.' },

    // Passage
    { id: 'passage',           text: passageText },
    { id: 'passage-label',     text: 'READING PASSAGE 2' },

    // Questions panel header
    { id: 'questions-label',   text: 'QUESTIONS' },
    { id: 'questions-subtext', text: 'Answer all questions based on Reading Passage 2' },

    // Q14-18 block
    { id: 'q14-18-title',         text: 'Questions 14–18' },
    { id: 'q14-18-instructions',  text: 'Do the following statements agree with the information given in Reading Passage 2?' },
    { id: 'q14-18-boxes',         text: 'In boxes 14-18 on your answer sheet, write -' },
    { id: 'true-label',           text: 'TRUE' },
    { id: 'true-desc',            text: 'if the statement agrees with the information' },
    { id: 'false-label',          text: 'FALSE' },
    { id: 'false-desc',           text: 'if the statement contradicts the information' },
    { id: 'not-given-label',      text: 'NOT GIVEN' },
    { id: 'not-given-desc',       text: 'if there is no information on this in the passage' },

    // Individual T/F/NG questions
    { id: 'q14-num', text: '14' },
    { id: 'q14',     text: 'The inventors of cinema regarded it as a minor attraction.' },
    { id: 'q15-num', text: '15' },
    { id: 'q15',     text: "Some directors were aware of cinema's artistic possibilities from the very beginning." },
    { id: 'q16-num', text: '16' },
    { id: 'q16',     text: "The development of cinema's artistic potential depended on technology." },
    { id: 'q17-num', text: '17' },
    { id: 'q17',     text: "Cinema's possibilities were developed in varied ways in different western countries." },
    { id: 'q18-num', text: '18' },
    { id: 'q18',     text: 'Western businessmen were concerned about the emergence of film industries in other parts of the world.' },

    // Q19-25 block
    { id: 'q19-25-title',        text: 'Questions 19–25' },
    { id: 'q19-25-instructions', text: 'Complete the notes below using the list of words' },
    { id: 'q19-25-ak',           text: '(A–K)' },
    { id: 'q19-25-from-box',     text: 'from the box below.' },
    { id: 'q19-25-write',        text: 'Write the correct letters in boxes' },
    { id: 'q19-25-numbers',      text: '19–25' },
    { id: 'q19-25-sheet',        text: 'on your answer sheet.' },
    { id: 'q19-25-choose',       text: 'Choose from the list of words below' },

    // Word list A-K
    { id: 'word-a-label',  text: 'A.' },
    { id: 'word-a',        text: 'emotional' },
    { id: 'word-b-label',  text: 'B.' },
    { id: 'word-b',        text: 'negative' },
    { id: 'word-c-label',  text: 'C.' },
    { id: 'word-c',        text: 'expensive' },
    { id: 'word-d-label',  text: 'D.' },
    { id: 'word-d',        text: 'silent' },
    { id: 'word-e-label',  text: 'E.' },
    { id: 'word-e',        text: 'social' },
    { id: 'word-f-label',  text: 'F.' },
    { id: 'word-f',        text: 'outstanding' },
    { id: 'word-g-label',  text: 'G.' },
    { id: 'word-g',        text: 'little' },
    { id: 'word-h-label',  text: 'H.' },
    { id: 'word-h',        text: 'powerful' },
    { id: 'word-i-label',  text: 'I.' },
    { id: 'word-i',        text: 'realistic' },
    { id: 'word-j-label',  text: 'J.' },
    { id: 'word-j',        text: 'stylistic' },
    { id: 'word-k-label',  text: 'K.' },
    { id: 'word-k',        text: 'economic' },

    // Notes table segments
    { id: 'chinese-cinema-label',   text: 'Chinese cinema' },
    { id: 'q19-before',             text: '• large number of' },
    { id: 'q19-after',              text: 'films produced in 1930s' },
    { id: 'q20-before',             text: '• some early films still generally regarded as' },
    { id: 'indian-cinema-label',    text: 'Indian cinema' },
    { id: 'q21-musical',            text: '• films included musical interludes' },
    { id: 'q21-before',             text: '• films avoided' },
    { id: 'q21-after',              text: 'topics' },
    { id: 'japanese-cinema-label',  text: 'Japanese cinema' },
    { id: 'q22-before',             text: '• unusual because film director was very' },
    { id: 'q23-two-directors',      text: '• two important directors:' },
    { id: 'q23-before',             text: 'Mizoguchi – focused on the' },
    { id: 'q23-after',              text: 'restrictions faced by women' },
    { id: 'q24-before',             text: '– camera movement related to' },
    { id: 'q24-after',              text: 'content of film' },
    { id: 'q25-before',             text: 'Ozu –' },
    { id: 'q25-after',              text: 'camera movement' },

    // Q26
    { id: 'q26-title',       text: 'Question 26' },
    { id: 'q26-instruction', text: 'Which of the following is the most suitable title for this Passage?' },
    { id: 'q26-a',           text: 'A. Blind to change: how is it that the west has ignored Asian cinema for so long?' },
    { id: 'q26-b',           text: 'B. A different basis: how has the cinema of Asian countries been shaped by their cultures and beliefs?' },
    { id: 'q26-c',           text: 'C. Outside Asia: how did the origins of cinema affect its development worldwide?' },
    { id: 'q26-d',           text: 'D. Two cultures: how has western cinema tried to come to terms with the challenge of the Asian market?' },
];

// Dynamically calculate cumulative offsets
let currentOffset = 0;
const textSegments = ref(
    allSegments.map((segment) => {
        const s = { id: segment.id, text: segment.text, offset: currentOffset };
        currentOffset += segment.text.length;
        return s;
    })
);

// ── Helper: get plain-text length of HTML string ─────────────────────────────
const getPlainTextLength = (htmlText: string): number => htmlText.replace(/<[^>]*>/g, '').length;

// ── Helper: map plain-text offset to HTML char index ─────────────────────────
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plain = 0, html = 0;
    while (plain < plainOffset && html < htmlText.length) {
        if (htmlText[html] === '<') {
            while (html < htmlText.length && htmlText[html] !== '>') html++;
            html++;
        } else {
            plain++;
            html++;
        }
    }
    return html;
};

// ── Core highlight renderer ───────────────────────────────────────────────────
const getHighlightedSegment = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentOffset = segment.offset;
    const segmentPlainLen = getPlainTextLength(segment.text);
    const segmentEnd = segmentOffset + segmentPlainLen;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset
    );

    if (!overlappingHighlights.length && !overlappingNotes.length) return segment.text;

    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset, end: h.end_offset,
            type: 'highlight' as const, color: h.color, id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start, end: n.end,
            type: 'note' as const, color: 'blue', id: n.id, noteText: n.note,
        })),
    ].sort((a, b) => b.start - a.start);

    let result = segment.text;
    for (const ann of annotations) {
        const plainStart = Math.max(0, ann.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainLen, ann.end - segmentOffset);
        if (plainStart >= plainEnd) continue;

        const htmlStart = plainToHtmlOffset(result, plainStart);
        const htmlEnd   = plainToHtmlOffset(result, plainEnd);
        const before    = result.substring(0, htmlStart);
        const middle    = result.substring(htmlStart, htmlEnd);
        const after     = result.substring(htmlEnd);

        if (ann.type === 'note') {
            result = `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${middle}</mark>${after}`;
        } else {
            result = `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${middle}</mark>${after}`;
        }
    }
    return result;
};

// ── Expose ────────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ── Selection / context-menu ─────────────────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }

        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node.nodeType !== Node.ELEMENT_NODE ? node.parentNode as Node : node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segmentId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

            let offsetInSegment = 0;
            const walker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let cur;
            while ((cur = walker.nextNode())) {
                if (cur === node) { offsetInSegment += offsetInNode; break; }
                offsetInSegment += cur.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbs = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbs   = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbs === null || endAbs === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }
        if (startAbs > endAbs) [startAbs, endAbs] = [endAbs, startAbs];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 78;
            const vw    = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value  = true;
            selectedText.value     = selection.toString();
            selectionRange.value   = { start: startAbs, end: endAbs };
        } else {
            showContextMenu.value = false;
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start)
    );
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const modalW = 320, modalH = 180, pad = 10;
    const selection = window.getSelection();
    let x = contextMenuPosition.value.x;
    let y = contextMenuPosition.value.y + 70;

    if (selection && selection.rangeCount > 0) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    }

    const vw = window.innerWidth, vh = window.innerHeight;
    x = Math.min(Math.max(x, modalW / 2 + pad), vw - modalW / 2 - pad);
    if (y + modalH > vh - pad) {
        if (selection && selection.rangeCount > 0) {
            const rect = selection.getRangeAt(0).getBoundingClientRect();
            y = rect.top - modalH - 10;
        }
        if (y < pad) y = pad;
    }

    noteInputPosition.value = { x, y };
    showNoteInput.value     = true;
    showContextMenu.value   = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: ns, end: ne } = selectionRange.value;
    findOverlappingHighlights(ns, ne).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < ne && n.end > ns));
    notes.value.push({
        id: Date.now(), text: selectedText.value, selectedText: selectedText.value,
        note: noteInputText.value.trim(), start: ns, end: ne, part: 'Part 2',
    });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };

const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const hm = target.closest('mark[data-highlight-id]') as HTMLElement;
    if (hm) {
        const hid = hm.getAttribute('data-highlight-id');
        if (hid) {
            event.stopPropagation();
            const rect = hm.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value     = parseInt(hid, 10);
            showDeleteTooltip.value     = true;
        }
    } else {
        closeDeleteTooltip();
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const nm = target.closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (nm) {
        const nid = nm.getAttribute('data-note-id');
        if (nid) {
            const note = notes.value.find((n) => n.id === parseInt(nid, 10));
            if (note) {
                const rect = nm.getBoundingClientRect();
                const ttH  = 50;
                let y = rect.top - ttH - 8;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value       = note.id;
                hoveredNoteText.value     = note.note;
                showNoteTooltip.value     = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const related = event.relatedTarget as HTMLElement;
    if (target.closest('mark[data-note-id]')) {
        if (related?.closest('.note-hover-tooltip')) return;
        showNoteTooltip.value = false;
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) { deleteNote(hoveredNoteId.value); showNoteTooltip.value = false; hoveredNoteId.value = null; }
};

const closeContextMenu   = () => { showContextMenu.value = false; };
const handleDeleteHighlight = (id: number) => { deleteHighlight(id); };
const handleClickOutside = () => { if (showContextMenu.value) showContextMenu.value = false; };
const handleKeyDown      = (event: KeyboardEvent) => { if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); } };

// ── Resize ────────────────────────────────────────────────────────────────────
const startResize   = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize  = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pct  = ((event.clientX - rect.left) / rect.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (v) => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

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

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32"
    >
        <!-- Part 2 Header -->
        <div class=" border-gray-400 mx-5 mt-4 part-header-box  px-4 py-2">
            <p
                class="text-sm font-bold text-gray-900 select-text"
                data-segment-id="part-header"
                v-html="getHighlightedSegment('part-header')"
            ></p>
            <p
                class="text-sm text-gray-700 select-text"
                data-segment-id="part-instructions"
                v-html="getHighlightedSegment('part-instructions')"
            ></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div
                ref="containerRef"
                class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }"
            >
                <!-- ── Reading Passage Panel ── -->
                <div
                    class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%`, width: `${leftPanelWidth}%` }"
                >


                    <div class="space-y-6 px-4" :style="contentZoom">
                        <div class="space-y-6 text-base leading-relaxed select-text sm:text-base">
                            <div class="px-4">
                                <div class="text-justify leading-relaxed font-medium whitespace-pre-wrap text-gray-700">
                                    <span
                                        class="text-base text-gray-700 select-text"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment('passage')"
                                    ></span>
                                </div>
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
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
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

                <!-- ── Questions Panel ── -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%`, width: `${100 - leftPanelWidth - 1.25}%` }"
                >
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════ Questions 14-18 (T/F/NG) ════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-4">
                                        <span
                                            data-segment-id="q14-18-title"
                                            v-html="getHighlightedSegment('q14-18-title')"
                                        ></span>
                                    </h3>
                                    <div class="border border-gray-300 p-6">
                                        <p class="text-base leading-relaxed font-medium text-gray-800">
                                            <span
                                                data-segment-id="q14-18-instructions"
                                                v-html="getHighlightedSegment('q14-18-instructions')"
                                            ></span>
                                        </p>
                                        <p class="text-base leading-relaxed font-medium text-gray-800">
                                            <span
                                                data-segment-id="q14-18-boxes"
                                                v-html="getHighlightedSegment('q14-18-boxes')"
                                            ></span>
                                        </p>
                                        <div class="grid grid-cols-1 gap-2 pt-4 text-base">
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900"
                                                    data-segment-id="true-label"
                                                    v-html="getHighlightedSegment('true-label')"
                                                ></span>
                                                <span
                                                    class="text-gray-700 select-text"
                                                    data-segment-id="true-desc"
                                                    v-html="getHighlightedSegment('true-desc')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-24 rounded bg-gray-100 px-2 py-1 font-bold"
                                                    data-segment-id="false-label"
                                                    v-html="getHighlightedSegment('false-label')"
                                                ></span>
                                                <span
                                                    class="text-gray-700 select-text"
                                                    data-segment-id="false-desc"
                                                    v-html="getHighlightedSegment('false-desc')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-700"
                                                    data-segment-id="not-given-label"
                                                    v-html="getHighlightedSegment('not-given-label')"
                                                ></span>
                                                <span
                                                    class="text-gray-700 select-text"
                                                    data-segment-id="not-given-desc"
                                                    v-html="getHighlightedSegment('not-given-desc')"
                                                ></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Q14 -->
                                    <div
                                        id="question-14"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="font-bold text-gray-900 select-text"
                                                data-segment-id="q14-num"
                                                v-html="getHighlightedSegment('q14-num')"
                                            ></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q14" v-html="getHighlightedSegment('q14')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q14" value="TRUE" class="h-4 w-4 accent-black" />
                                                        <span>TRUE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q14" value="FALSE" class="h-4 w-4 accent-black" />
                                                        <span>FALSE</span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q14" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span>NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-show="isBookmarkVisible(14)"
                                            @click="emit('toggleFlag', 14)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q15 -->
                                    <div
                                        id="question-15"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q15-num" v-html="getHighlightedSegment('q15-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700">
                                                    <span class="select-text" data-segment-id="q15" v-html="getHighlightedSegment('q15')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q15" value="TRUE" class="h-4 w-4 accent-black" /><span>TRUE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q15" value="FALSE" class="h-4 w-4 accent-black" /><span>FALSE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q15" value="NOT GIVEN" class="h-4 w-4 accent-black" /><span>NOT GIVEN</span></label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(15)" @click="emit('toggleFlag', 15)" class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all" :class="isQuestionFlagged(15) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                    </div>

                                    <!-- Q16 -->
                                    <div id="question-16" class="relative pr-10" @mouseenter="hoveredQuestion = 16" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q16-num" v-html="getHighlightedSegment('q16-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700"><span class="select-text" data-segment-id="q16" v-html="getHighlightedSegment('q16')"></span></p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q16" value="TRUE" class="h-4 w-4 accent-black" /><span>TRUE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q16" value="FALSE" class="h-4 w-4 accent-black" /><span>FALSE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q16" value="NOT GIVEN" class="h-4 w-4 accent-black" /><span>NOT GIVEN</span></label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(16)" @click="emit('toggleFlag', 16)" class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all" :class="isQuestionFlagged(16) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                    </div>

                                    <!-- Q17 -->
                                    <div id="question-17" class="relative pr-10" @mouseenter="hoveredQuestion = 17" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q17-num" v-html="getHighlightedSegment('q17-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700"><span class="select-text" data-segment-id="q17" v-html="getHighlightedSegment('q17')"></span></p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q17" value="TRUE" class="h-4 w-4 accent-black" /><span>TRUE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q17" value="FALSE" class="h-4 w-4 accent-black" /><span>FALSE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q17" value="NOT GIVEN" class="h-4 w-4 accent-black" /><span>NOT GIVEN</span></label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(17)" @click="emit('toggleFlag', 17)" class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all" :class="isQuestionFlagged(17) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                    </div>

                                    <!-- Q18 -->
                                    <div id="question-18" class="relative pr-10" @mouseenter="hoveredQuestion = 18" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q18-num" v-html="getHighlightedSegment('q18-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700"><span class="select-text" data-segment-id="q18" v-html="getHighlightedSegment('q18')"></span></p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q18" value="TRUE" class="h-4 w-4 accent-black" /><span>TRUE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q18" value="FALSE" class="h-4 w-4 accent-black" /><span>FALSE</span></label>
                                                    <label class="flex cursor-pointer items-center gap-2"><input type="radio" v-model="answers.q18" value="NOT GIVEN" class="h-4 w-4 accent-black" /><span>NOT GIVEN</span></label>
                                                </div>
                                            </div>
                                        </div>
                                        <button v-show="isBookmarkVisible(18)" @click="emit('toggleFlag', 18)" class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all" :class="isQuestionFlagged(18) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                    </div>
                                </div>
                            </div>

                            <!-- ════ Questions 19-25 (drag & drop notes table) ════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-base font-bold text-gray-900 mb-4">
                                        <span data-segment-id="q19-25-title" v-html="getHighlightedSegment('q19-25-title')"></span>
                                    </h3>
                                    <p class="mb-4 text-base leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q19-25-instructions" v-html="getHighlightedSegment('q19-25-instructions')"></span>
                                        <strong data-segment-id="q19-25-ak" v-html="getHighlightedSegment('q19-25-ak')"></strong>
                                        <span data-segment-id="q19-25-from-box" v-html="getHighlightedSegment('q19-25-from-box')"></span><br />
                                        <span data-segment-id="q19-25-write" v-html="getHighlightedSegment('q19-25-write')"></span>
                                        <strong data-segment-id="q19-25-numbers" v-html="getHighlightedSegment('q19-25-numbers')"></strong>
                                        <span data-segment-id="q19-25-sheet" v-html="getHighlightedSegment('q19-25-sheet')"></span>
                                    </p>
                                </div>

                                <!-- Two-column layout: notes table (left) + draggable word bank (right) -->
                                <div class="flex gap-6">
                                    <!-- Left: notes table with drop zones -->
                                    <div class="flex-1 border border-gray-300 p-4">

                                        <!-- Word bank header inside box -->


                                        <!-- Chinese cinema -->
                                        <div class="mb-3">
                                            <p class="font-bold text-gray-900 text-sm mb-2"
                                               data-segment-id="chinese-cinema-label"
                                               v-html="getHighlightedSegment('chinese-cinema-label')"></p>
                                            <ul class="list-none space-y-3 text-sm">
                                                <!-- Q19 -->
                                                <li id="question-19"
                                                    class="flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 19"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q19-before" v-html="getHighlightedSegment('q19-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 19 ? 'border-blue-500 bg-blue-50' : answers.q19 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 19)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 19)"
                                                            @click="clearAnswer(19)"
                                                            :title="answers.q19 ? 'Click to clear' : 'Drop answer here'"

                                                        >{{ getDropLabel('q19') || '19' }}</span>
                                                        <button
                                                            v-show="isBookmarkVisible(19)"
                                                            @click.stop="emit('toggleFlag', 19)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(19) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                    <span data-segment-id="q19-after" v-html="getHighlightedSegment('q19-after')"></span>
                                                </li>

                                                <!-- Q20 -->
                                                <li id="question-20"
                                                    class="flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 20"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q20-before" v-html="getHighlightedSegment('q20-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 20 ? 'border-blue-500 bg-blue-50' : answers.q20 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 20)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 20)"
                                                            @click="clearAnswer(20)"
                                                            :title="answers.q20 ? 'Click to clear' : 'Drop answer here'"
                                                        >{{ getDropLabel('q20') || '20' }}</span>
                                                        <button
                                                            v-show="isBookmarkVisible(20)"
                                                            @click.stop="emit('toggleFlag', 20)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(20) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Indian cinema -->
                                        <div class="mb-3">
                                            <p class="font-bold text-gray-900 text-sm mb-2"
                                               data-segment-id="indian-cinema-label"
                                               v-html="getHighlightedSegment('indian-cinema-label')"></p>
                                            <ul class="list-none space-y-3 text-sm">
                                                <li data-segment-id="q21-musical" v-html="getHighlightedSegment('q21-musical')"></li>

                                                <!-- Q21 -->
                                                <li id="question-21"
                                                    class="flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 21"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q21-before" v-html="getHighlightedSegment('q21-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 21 ? 'border-blue-500 bg-blue-50' : answers.q21 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 21)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 21)"
                                                            @click="clearAnswer(21)"
                                                            :title="answers.q21 ? 'Click to clear' : 'Drop answer here'"
                                                        >{{ getDropLabel('q21') || '21' }}</span>
                                                        <button
                                                            v-show="isBookmarkVisible(21)"
                                                            @click.stop="emit('toggleFlag', 21)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(21) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                    <span data-segment-id="q21-after" v-html="getHighlightedSegment('q21-after')"></span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Japanese cinema -->
                                        <div class="mb-3">
                                            <p class="font-bold text-gray-900 text-sm mb-2"
                                               data-segment-id="japanese-cinema-label"
                                               v-html="getHighlightedSegment('japanese-cinema-label')"></p>
                                            <ul class="list-none space-y-3 text-sm">
                                                <!-- Q22 -->
                                                <li id="question-22"
                                                    class="flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 22"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q22-before" v-html="getHighlightedSegment('q22-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 22 ? 'border-blue-500 bg-blue-50' : answers.q22 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 22)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 22)"
                                                            @click="clearAnswer(22)"
                                                            :title="answers.q22 ? 'Click to clear' : 'Drop answer here'"
                                                        >{{ getDropLabel('q22') || '22' }}</span>
                                                        <button
                                                            v-show="isBookmarkVisible(22)"
                                                            @click.stop="emit('toggleFlag', 22)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(22) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                </li>

                                                <li data-segment-id="q23-two-directors" v-html="getHighlightedSegment('q23-two-directors')"></li>

                                                <!-- Q23 Mizoguchi -->
                                                <li id="question-23"
                                                    class="ml-4 flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 23"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q23-before" v-html="getHighlightedSegment('q23-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 23 ? 'border-blue-500 bg-blue-50' : answers.q23 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 23)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 23)"
                                                            @click="clearAnswer(23)"
                                                            :title="answers.q23 ? 'Click to clear' : 'Drop answer here'"
                                                        >{{ getDropLabel('q23') || '23' }}</span>
                                                        <button
                                                            v-show="isBookmarkVisible(23)"
                                                            @click.stop="emit('toggleFlag', 23)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(23) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                    <span data-segment-id="q23-after" v-html="getHighlightedSegment('q23-after')"></span>
                                                </li>

                                                <!-- Q24 camera movement Mizoguchi -->
                                                <li id="question-24"
                                                    class="ml-4 flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 24"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q24-before" v-html="getHighlightedSegment('q24-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 24 ? 'border-blue-500 bg-blue-50' : answers.q24 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 24)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 24)"
                                                            @click="clearAnswer(24)"
                                                            :title="answers.q24 ? 'Click to clear' : 'Drop answer here'"
                                                        >{{ getDropLabel('q24') || '24' }}</span>
                                                        <button
                                                            v-show="isBookmarkVisible(24)"
                                                            @click.stop="emit('toggleFlag', 24)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(24) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                    <span data-segment-id="q24-after" v-html="getHighlightedSegment('q24-after')"></span>
                                                </li>

                                                <!-- Q25 Ozu -->
                                                <li id="question-25"
                                                    class="ml-4 flex flex-wrap items-center gap-1"
                                                    @mouseenter="hoveredQuestion = 25"
                                                    @mouseleave="hoveredQuestion = null">
                                                    <span data-segment-id="q25-before" v-html="getHighlightedSegment('q25-before')"></span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <span
                                                            class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-2 py-1 text-xs transition-all cursor-pointer"
                                                            :class="dragOverQuestion === 25 ? 'border-blue-500 bg-blue-50' : answers.q25 ? 'border-gray-800 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                            @dragover="handleDragOver($event, 25)"
                                                            @dragleave="handleDragLeave"
                                                            @drop="handleDrop($event, 25)"
                                                            @click="clearAnswer(25)"
                                                            :title="answers.q25 ? 'Click to clear' : 'Drop answer here'"
                                                        >{{ getDropLabel('q25') || '25' }}</span>

                                                    <span data-segment-id="q25-after" v-html="getHighlightedSegment('q25-after')"></span>
                                                        <button
                                                            v-show="isBookmarkVisible(25)"
                                                            @click.stop="emit('toggleFlag', 25)"
                                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(25) ? 'border-red-400 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-500'"
                                                        ><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg></button>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Right: sticky draggable word bank -->
                                    <div class="w-44 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-bold text-gray-700">Drag to fill blanks:</p>
                                        <div class="border border-gray-900 bg-white p-2">
                                            <div class="space-y-1">
                                                <div
                                                    v-for="opt in wordOptions"
                                                    :key="opt.value"
                                                    class="flex cursor-grab items-center gap-1.5 rounded border border-gray-300 px-2 py-1 text-xs transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-40': draggedOption === opt.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, opt.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="font-bold text-gray-900">{{ opt.value }}.</span>
                                                    <span class="text-gray-700">{{ opt.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">Click a filled box to clear it.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ════ Question 26 (multiple choice) ════ -->
                            <div id="question-26" class="group/q26 p-6">
                                <div class="mb-4 flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 mb-2">
                                            <span data-segment-id="q26-title" v-html="getHighlightedSegment('q26-title')"></span>
                                        </h3>
                                        <p class="text-base leading-relaxed text-gray-700">
                                            <span data-segment-id="q26-instruction" v-html="getHighlightedSegment('q26-instruction')"></span>
                                        </p>
                                    </div>
                                    <button
                                        @click.stop="emit('toggleFlag', 26)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all duration-150
                                            opacity-5 group-hover/q26:opacity-100"
                                        :class="isQuestionFlagged(26) ? 'border-red-300 text-red-500 opacity-100!' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        title="Bookmark"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="opt in ['a', 'b', 'c', 'd']"
                                        :key="opt"
                                        class="p-2"
                                    >
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input
                                                type="radio"
                                                :value="opt.toUpperCase()"
                                                v-model="answers.q26"
                                                class="h-5 w-5 accent-black"
                                            />
                                            <span
                                                class="text-base font-medium text-gray-800 select-text"
                                                :data-segment-id="`q26-${opt}`"
                                                v-html="getHighlightedSegment(`q26-${opt}`)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /space-y-8 -->
                    </div><!-- /flex-1 overflow -->
                </div><!-- /answer-panel -->
            </div><!-- /containerRef -->
        </div>

        <!-- ── Teleported Overlays ── -->
        <Teleport to="body">
            <!-- Context Menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-7 w-7 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                            <svg class="h-7 w-7 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
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
                            <svg class="h-7 w-7 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
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
                        <span class="note-tooltip-icon shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button @click="deleteNoteFromTooltip" class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
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
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
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
                        class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                    <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.part-header-box { background-color: #F1F2EC; }

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

/* Highlight functionality styles */
.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
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
