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

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
};

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// ── Resize ────────────────────────────────────────────────────────────────────
const PANEL_WIDTH_KEY = 'reading-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// ── Highlight ─────────────────────────────────────────────────────────────────
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

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// ── Answers ───────────────────────────────────────────────────────────────────
const answers = ref({
    q27: '',
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

// ── Drag & Drop Q31-34 (word bank, depleting) ─────────────────────────────────
const draggedItem = ref<string | null>(null);
const dragSource = ref<'bank31' | 'bank35' | number | null>(null);
const dragOverQuestion = ref<number | null>(null);

const wordBankLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
const wordBankLabels: Record<string, string> = {
    A: 'inventive',
    B: 'similar',
    C: 'beneficial',
    D: 'next',
    E: 'mixed',
    F: 'justified',
    G: 'inferior',
};

const usedLetters31_34 = computed(() =>
    [answers.value.q31, answers.value.q32, answers.value.q33, answers.value.q34].filter(Boolean)
);
const bankLetters31_34 = computed(() =>
    wordBankLetters.filter((l) => !usedLetters31_34.value.includes(l))
);

const getAnswerForQ = (qNum: number): string =>
    (answers.value as any)[`q${qNum}`] as string;

const setAnswerForQ = (qNum: number, val: string) => {
    (answers.value as any)[`q${qNum}`] = val;
};

// Q31-34 drag handlers
const onDragStartFromBank31 = (event: DragEvent, letter: string) => {
    draggedItem.value = letter;
    dragSource.value = 'bank31';
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', letter);
};

const onDragStartFromSlot31_34 = (event: DragEvent, letter: string, qNum: number) => {
    draggedItem.value = letter;
    dragSource.value = qNum;
    event.dataTransfer!.effectAllowed = 'move';
    event.dataTransfer!.setData('text/plain', letter);
};

const onDropToSlot31_34 = (event: DragEvent, targetQ: number) => {
    event.preventDefault();
    dragOverQuestion.value = null;
    if (!draggedItem.value) return;
    const incoming = draggedItem.value;
    const currentInTarget = getAnswerForQ(targetQ);

    if (dragSource.value === 'bank31') {
        setAnswerForQ(targetQ, incoming);
    } else if (typeof dragSource.value === 'number') {
        const sourceQ = dragSource.value as number;
        if (sourceQ >= 31 && sourceQ <= 34 && targetQ >= 31 && targetQ <= 34 && sourceQ !== targetQ) {
            setAnswerForQ(sourceQ, currentInTarget);
            setAnswerForQ(targetQ, incoming);
        }
    }
    draggedItem.value = null;
    dragSource.value = null;
};

const onDropToBank31 = (event: DragEvent) => {
    event.preventDefault();
    if (draggedItem.value && typeof dragSource.value === 'number') {
        const srcQ = dragSource.value as number;
        if (srcQ >= 31 && srcQ <= 34) setAnswerForQ(srcQ, '');
    }
    draggedItem.value = null;
    dragSource.value = null;
};

// ── Drag & Drop Q35-40 (people options, reusable — same as Q24-27 in doc3) ───
const peopleOptions = [
    { value: 'A', label: 'Jean-Auguste-Dominique Ingres' },
    { value: 'B', label: 'Francis Wey' },
    { value: 'C', label: 'Charles Baudelaire' },
    { value: 'D', label: 'Eugene Delacroix' },
    { value: 'E', label: 'Philip Gilbert Hamerton' },
];

const getPeopleLabel = (value: string): string => {
    const opt = peopleOptions.find((o) => o.value === value);
    return opt ? `${opt.value}  ${opt.label}` : '';
};

// Q35-40 use simple HTML5 drag (same as doc3 Q24-27 pattern)
const handleDragStart35 = (event: DragEvent, value: string) => {
    draggedItem.value = value;
    dragSource.value = 'bank35';
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('text/plain', value);
    }
};

const handleDragEnd35 = () => {
    draggedItem.value = null;
    dragSource.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver35 = (event: DragEvent, qNum: number) => {
    event.preventDefault();
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
    dragOverQuestion.value = qNum;
};

const handleDragLeave35 = () => {
    dragOverQuestion.value = null;
};

const handleDrop35 = (event: DragEvent, qNum: number) => {
    event.preventDefault();
    const value = event.dataTransfer?.getData('text/plain') || draggedItem.value;
    if (value) setAnswerForQ(qNum, value);
    draggedItem.value = null;
    dragSource.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer35 = (qNum: number) => {
    setAnswerForQ(qNum, '');
};

const onDragOver31 = (event: DragEvent) => {
    event.preventDefault();
    event.dataTransfer!.dropEffect = 'move';
};

// ── Text segments ─────────────────────────────────────────────────────────────
const passageText = `This may seem a pointless question today. Surrounded as we are by thousands of photographs, most of us take for granted that, in addition to supplying information and seducing customers, camera images also serve as decoration, afford spiritual enrichment, and provide significant insights into the passing scene. But in the decades following the discovery of photography, this question reflected the search for ways to fit the mechanical medium into the traditional schemes of artistic expression.
The much-publicized pronouncement by painter Paul Delaroche that the daguerreotype signalled the end of painting is perplexing because this clever artist also forecast the usefulness of the medium for graphic artists in a letter written in 1839. Nevertheless, it is symptomatic of the swing between the outright rejection and qualified acceptance of the medium that was fairly typical of the artistic establishment. Discussion of the role of photography in art was especially spirited in France, where the internal policies of the time had created a large pool of artists, but it was also taken up by important voices in England. In both countries, public interest in this topic was a reflection of the belief that national stature and achievement in the arts were related.
From the maze of conflicting statements and heated articles on the subject, three main positions about the potential of camera art emerged. The simplest, entertained by many painters and a section of the public, was that photographs should not be considered 'art' because they were made with a mechanical device and by physical and chemical phenomena instead of by human hand and spirit; to some, camera images seemed to have more in common with fabric produced by machinery in a mill than with handmade creations fired by inspiration. The second widely held view, shared by painters, some photographers, and some critics, was that photographs would be useful to art but should not be considered equal in creativeness to drawing and painting. Lastly, by assuming that the process was comparable to other techniques such as etching and lithography, a fair number of individuals realized that camera images were or could be as significant as handmade works of art and that they might have a positive influence on the arts and on culture in general.
Artists reacted to photography in various ways. Many portrait painters - miniaturists in particular - who realized that photography represented the 'handwriting on the wall' became involved with daguerreotyping or paper photography in an effort to save their careers; some incorporated it with painting, while others renounced painting altogether. Still other painters, the most prominent among them the French painter, Jean-Auguste-Dominique Ingres, began almost immediately to use photography to make a record of their own output and also to provide themselves with source material for poses and backgrounds, vigorously denying at the same time its influence on their vision or its claims as art.
The view that photographs might be worthwhile to artists was enunciated in considerable detail by Lacan and Francis Wey. The latter, an art and literary critic, who eventually recognised that camera images could be inspired as well as informative, suggested that they would lead to greater naturalness in the graphic depiction of anatomy, clothing, likeness, expression, and landscape. By studying photographs, true artists, he claimed, would be relieved of menial tasks and become free to devote themselves to the more important spiritual aspects of their work.
Wey left unstated what the incompetent artist might do as an alternative, but according to the influential French critic and poet Charles Baudelaire, writing in response to an exhibition of photography in 1859, lazy and untalented painters would become photographers. Fired by a belief in art as an imaginative embodiment of cultivated ideas and dreams, Baudelaire regarded photography as 'a very humble servant of art and science'; a medium largely unable to transcend 'external reality'. For this critic, photography was linked with 'the great industrial madness' of the time, which in his eyes exercised disastrous consequences on the spiritual qualities of life and art.
Eugene Delacroix was the most prominent of the French artists who welcomed photography as help-mate but recognized its limitations. Regretting that 'such a wonderful invention' had arrived so late in his lifetime, he still took lessons in daguerreotyping, and both commissioned and collected photographs. Delacroix's enthusiasm for the medium can be sensed in a journal entry noting that if photographs were used as they should be, an artist might 'raise himself to heights that we do not yet know'.
The question of whether the photograph was document or art aroused interest in England also. The most important statement on this matter was an unsigned article that concluded that while photography had a role to play, it should not be 'constrained' into 'competition' with art; a more stringent viewpoint led critic Philip Gilbert Hamerton to dismiss camera images as 'narrow in range, emphatic in assertion, telling one truth for ten falsehoods'.
These writers reflected the opposition of a section of the cultural elite in England and France to the 'cheapening of art' which the growing acceptance and purchase of camera pictures by the middle class represented. Technology made photographic images a common sight in the shop windows of Regent Street and Piccadilly in London and the commercial boulevards of Paris. In London, for example, there were at the time some 130 commercial establishments where portraits, landscapes, and photographic reproductions of works of art could be bought. This appeal to the middle class convinced the elite that photographs would foster a desire for realism instead of idealism, even though some critics recognized that the work of individual photographers might display an uplifting style and substance that was consistent with the defining characteristics of art.`;

const passageHtml = `<i>This may seem a pointless question today.</i> Surrounded as we are by thousands of photographs, most of us take for granted that, in addition to supplying information and seducing customers, camera images also serve as decoration, afford spiritual enrichment, and provide significant insights into the passing scene. But in the decades following the discovery of photography, this question reflected the search for ways to fit the mechanical medium into the traditional schemes of artistic expression.<br/><br/>
The much-publicized pronouncement by painter Paul Delaroche that the daguerreotype* signalled the end of painting is perplexing because this clever artist also forecast the usefulness of the medium for graphic artists in a letter written in 1839. Nevertheless, it is symptomatic of the swing between the outright rejection and qualified acceptance of the medium that was fairly typical of the artistic establishment. Discussion of the role of photography in art was especially spirited in France, where the internal policies of the time had created a large pool of artists, but it was also taken up by important voices in England. In both countries, public interest in this topic was a reflection of the belief that national stature and achievement in the arts were related.<br/><br/>
From the maze of conflicting statements and heated articles on the subject, three main positions about the potential of camera art emerged. The simplest, entertained by many painters and a section of the public, was that photographs should not be considered 'art' because they were made with a mechanical device and by physical and chemical phenomena instead of by human hand and spirit; to some, camera images seemed to have more in common with fabric produced by machinery in a mill than with handmade creations fired by inspiration. The second widely held view, shared by painters, some photographers, and some critics, was that photographs would be useful to art but should not be considered equal in creativeness to drawing and painting. Lastly, by assuming that the process was comparable to other techniques such as etching and lithography, a fair number of individuals realized that camera images were or could be as significant as handmade works of art and that they might have a positive influence on the arts and on culture in general.<br/><br/>
Artists reacted to photography in various ways. Many portrait painters - miniaturists in particular - who realized that photography represented the 'handwriting on the wall' became involved with daguerreotyping or paper photography in an effort to save their careers; some incorporated it with painting, while others renounced painting altogether. Still other painters, the most prominent among them the French painter, Jean-Auguste-Dominique Ingres, began almost immediately to use photography to make a record of their own output and also to provide themselves with source material for poses and backgrounds, vigorously denying at the same time its influence on their vision or its claims as art.<br/><br/>
The view that photographs might be worthwhile to artists was enunciated in considerable detail by Lacan and Francis Wey. The latter, an art and literary critic, who eventually recognised that camera images could be inspired as well as informative, suggested that they would lead to greater naturalness in the graphic depiction of anatomy, clothing, likeness, expression, and landscape. By studying photographs, true artists, he claimed, would be relieved of menial tasks and become free to devote themselves to the more important spiritual aspects of their work.<br/><br/>
Wey left unstated what the incompetent artist might do as an alternative, but according to the influential French critic and poet Charles Baudelaire, writing in response to an exhibition of photography in 1859, lazy and untalented painters would become photographers. Fired by a belief in art as an imaginative embodiment of cultivated ideas and dreams, Baudelaire regarded photography as 'a very humble servant of art and science'; a medium largely unable to transcend 'external reality'. For this critic, photography was linked with 'the great industrial madness' of the time, which in his eyes exercised disastrous consequences on the spiritual qualities of life and art.<br/><br/>
Eugene Delacroix was the most prominent of the French artists who welcomed photography as help-mate but recognized its limitations. Regretting that 'such a wonderful invention' had arrived so late in his lifetime, he still took lessons in daguerreotyping, and both commissioned and collected photographs. Delacroix's enthusiasm for the medium can be sensed in a journal entry noting that if photographs were used as they should be, an artist might 'raise himself to heights that we do not yet know'.<br/><br/>
The question of whether the photograph was document or art aroused interest in England also. The most important statement on this matter was an unsigned article that concluded that while photography had a role to play, it should not be 'constrained' into 'competition' with art; a more stringent viewpoint led critic Philip Gilbert Hamerton to dismiss camera images as 'narrow in range, emphatic in assertion, telling one truth for ten falsehoods'.<br/><br/>
These writers reflected the opposition of a section of the cultural elite in England and France to the 'cheapening of art' which the growing acceptance and purchase of camera pictures by the middle class represented. Technology made photographic images a common sight in the shop windows of Regent Street and Piccadilly in London and the commercial boulevards of Paris. In London, for example, there were at the time some 130 commercial establishments where portraits, landscapes, and photographic reproductions of works of art could be bought. This appeal to the middle class convinced the elite that photographs would foster a desire for realism instead of idealism, even though some critics recognized that the work of individual photographers might display an uplifting style and substance that was consistent with the defining characteristics of art.<br/><br/>
<span class="text-sm text-gray-500">* the name given to the first commercially successful photographic images.</span>`;

// ── All segments (doc3 pattern: named ids, cumulative offsets) ────────────────
const allSegments = [
    { id: 'part-header', text: 'Part 2' },
    { id: 'part-instructions', text: 'Read the text and answer questions 27–40.' },
    { id: 'passage-title', text: 'IS PHOTOGRAPHY ART?' },
    { id: 'passage', text: passageText },

    { id: 'q27-30-title', text: 'Questions 27–30' },
    { id: 'q27-30-inst1', text: 'Choose the correct letter, A, B, C or D.' },
    { id: 'q27-30-inst2', text: 'Write your answers in boxes 27–30 on your answer sheet.' },
    { id: 'q27-text', text: "What is the writer's main point in the first paragraph?" },
    { id: 'q27-optA', text: 'photography is used for many different purposes.' },
    { id: 'q27-optB', text: 'photographers and artists have the same principal aims.' },
    { id: 'q27-optC', text: 'Photography has not always been a readily accepted art form.' },
    { id: 'q27-optD', text: 'photographers today are more creative than those of the past.' },
    { id: 'q28-text', text: 'What public view about artists was shared by the French and the English?' },
    { id: 'q28-optA', text: "that only artists could reflect a culture's true values" },
    { id: 'q28-optB', text: 'that only artists were qualified to judge photography' },
    { id: 'q28-optC', text: 'that artists could lose work as a result of photography' },
    { id: 'q28-optD', text: "that artist success raised a country's international profile" },
    { id: 'q29-text', text: 'What does the writer mean by "the handwriting on the wall"?' },
    { id: 'q29-optA', text: 'an example of poor talent' },
    { id: 'q29-optB', text: 'a message that cannot be trusted' },
    { id: 'q29-optC', text: 'an advertisement for something new' },
    { id: 'q29-optD', text: 'a signal that something bad will happen' },
    { id: 'q30-text', text: 'What was the result of the widespread availability of photographs to the middle classes?' },
    { id: 'q30-optA', text: 'The most educated worried about its impact on public taste.' },
    { id: 'q30-optB', text: 'It helped artists appreciate the merits of photography.' },
    { id: 'q30-optC', text: 'Improvements were made in photographic methods.' },
    { id: 'q30-optD', text: 'It led to a reduction in the price of photographs.' },

    { id: 'q31-34-title', text: 'Questions 31–34' },
    { id: 'q31-34-inst1', text: 'Complete the summary of Paragraph 3 using the list of words, A–G, below.' },
    { id: 'q31-34-inst2', text: 'Write your answers in boxes 31–34 on your answer sheet.' },
    { id: 'q31-34-heading', text: 'Camera art' },
    { id: 'q31-pre', text: 'In the early days of photography, opinions on its future were' },
    { id: 'q31-post', text: ', but three clear views emerged. A large number of artists and ordinary people saw photographs as' },
    { id: 'q32-post', text: 'to paintings because of the way they were produced. Another popular view was that photographs could have a role to play in the art world, despite the photographer being less' },
    { id: 'q33-post', text: 'Finally, a smaller number of people suspected that the impact of photography on art and society could be' },

    { id: 'q35-40-title', text: 'Questions 35–40' },
    { id: 'q35-40-inst1', text: 'Look at the following statements and the list of people, A–E, below.' },
    { id: 'q35-40-inst2', text: 'Match each statement with the correct person.' },
    { id: 'q35-40-inst3', text: 'Write the correct letter, A–E, in boxes 35–40 on your answer sheet.' },
    { id: 'q35-40-nb', text: 'NB You may use any letter more than once.' },
    { id: 'q35-text', text: 'He claimed that photography would make paintings more realistic.' },
    { id: 'q36-text', text: 'He highlighted the limitations and deceptions of the camera.' },
    { id: 'q37-text', text: 'He documented his production of artwork by photographing his works.' },
    { id: 'q38-text', text: 'He noted the potential for photography to enrich artistic talent.' },
    { id: 'q39-text', text: 'He based some of the scenes in his paintings on photographs.' },
    { id: 'q40-text', text: 'He felt photography was part of the trend towards greater mechanisation.' },
];

let currentOffset = 0;
const textSegments = ref(
    allSegments.map((seg) => {
        const s = { id: seg.id, text: seg.text, offset: currentOffset };
        currentOffset += seg.text.length;
        return s;
    })
);

// ── Highlight helpers ─────────────────────────────────────────────────────────
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let pi = 0, hi = 0;
    while (pi < plainOffset && hi < htmlText.length) {
        if (htmlText[hi] === '<') {
            while (hi < htmlText.length && htmlText[hi] !== '>') hi++;
            hi++;
        } else { pi++; hi++; }
    }
    return hi;
};

const getPlainTextLength = (html: string): number => html.replace(/<[^>]*>/g, '').length;

const getHighlightedSegment = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segText = segment.text;
    const segOffset = segment.offset;
    const segPlainLen = getPlainTextLength(segText);
    const segEnd = segOffset + segPlainLen;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segEnd && h.end_offset > segOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segEnd && n.end > segOffset);

    if (!overlappingHighlights.length && !overlappingNotes.length) return segText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    let result = segText;
    for (const ann of annotations) {
        const ps = Math.max(0, ann.start - segOffset);
        const pe = Math.min(segPlainLen, ann.end - segOffset);
        if (ps >= pe) continue;
        const hs = plainToHtmlOffset(result, ps);
        const he = plainToHtmlOffset(result, pe);
        const mid = result.substring(hs, he);
        result = result.substring(0, hs) +
            (ann.type === 'note'
                ? `<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${mid}</mark>`
                : `<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${mid}</mark>`) +
            result.substring(he);
    }
    return result;
};

const getHighlightedPassage = (): string => {
    const seg = textSegments.value.find((s) => s.id === 'passage')!;
    const segOffset = seg.offset;
    const segPlainLen = getPlainTextLength(passageText);
    const segEnd = segOffset + segPlainLen;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segEnd && h.end_offset > segOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segEnd && n.end > segOffset);

    if (!overlappingHighlights.length && !overlappingNotes.length) return passageHtml;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    let result = passageHtml;
    for (const ann of annotations) {
        const ps = Math.max(0, ann.start - segOffset);
        const pe = Math.min(segPlainLen, ann.end - segOffset);
        if (ps >= pe) continue;
        const hs = plainToHtmlOffset(result, ps);
        const he = plainToHtmlOffset(result, pe);
        const mid = result.substring(hs, he);
        result = result.substring(0, hs) +
            (ann.type === 'note'
                ? `<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${mid}</mark>`
                : `<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${mid}</mark>`) +
            result.substring(he);
    }
    return result;
};

// ── Expose ────────────────────────────────────────────────────────────────────
const getAnswers = () => answers.value;
watch(leftPanelWidth, (v) => localStorage.setItem(PANEL_WIDTH_KEY, String(v)));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const el = document.getElementById(`question-${questionNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('highlight-question');
        setTimeout(() => el.classList.remove('highlight-question'), 2000);
    }
};

// ── TreeWalker-based multiline highlight handler (doc3 pattern) ───────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }

        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection.getRangeAt(0);

        // Walk up from node to find nearest [data-segment-id] ancestor
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container: Node = node.nodeType !== Node.ELEMENT_NODE ? (node.parentNode as Node) : node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;

            const segId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find((s) => s.id === segId);
            if (!segment) return null;

            // TreeWalker counts plain-text chars up to the target node
            let offsetInSegment = 0;
            const walker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let cur: Node | null;
            while ((cur = walker.nextNode())) {
                if (cur === node) {
                    offsetInSegment += offsetInNode;
                    break;
                }
                offsetInSegment += cur.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbs = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbs = getAbsoluteOffset(range.endContainer, range.endOffset);

        if (startAbs === null || endAbs === null) {
            showContextMenu.value = false;
            window.getSelection()?.removeAllRanges();
            return;
        }

        if (startAbs > endAbs) [startAbs, endAbs] = [endAbs, startAbs];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const vw = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(rect.left + rect.width / 2, 80), vw - 80),
                y: Math.max(rect.top - 78, 10),
            };
            showContextMenu.value = true;
            selectedText.value = selection.toString();
            selectionRange.value = { start: startAbs, end: endAbs };
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
    const mw = 320, mh = 180, pad = 10;
    const sel = window.getSelection();
    let x: number, y: number;
    if (sel && sel.rangeCount > 0) {
        const r = sel.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    } else {
        x = contextMenuPosition.value.x; y = contextMenuPosition.value.y + 70;
    }
    const vw = window.innerWidth, vh = window.innerHeight, hw = mw / 2;
    if (x - hw < pad) x = hw + pad;
    else if (x + hw > vw - pad) x = vw - hw - pad;
    if (y + mh > vh - pad) {
        if (sel && sel.rangeCount > 0) y = sel.getRangeAt(0).getBoundingClientRect().top - mh - 10;
        if (y < pad) y = pad;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true; showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start, end } = selectionRange.value;
    findOverlappingHighlights(start, end).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < end && n.end > start));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start, end, part: 'Part 3' });
    noteInputText.value = ''; showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const hm = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement | null;
    if (hm) {
        const hid = hm.getAttribute('data-highlight-id');
        if (hid) {
            event.stopPropagation();
            const r = hm.getBoundingClientRect();
            deleteTooltipPosition.value = { x: r.left + r.width / 2, y: r.bottom + 8 };
            highlightToDelete.value = parseInt(hid, 10);
            showDeleteTooltip.value = true; showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
    }
};

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const nm = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement | null;
    if (nm) {
        const nid = nm.getAttribute('data-note-id');
        if (nid) {
            const id = parseInt(nid, 10);
            const note = notes.value.find((n) => n.id === id);
            if (note) {
                const r = nm.getBoundingClientRect();
                let y = r.top - 58; if (y < 10) y = r.bottom + 8;
                noteTooltipPosition.value = { x: r.left + r.width / 2, y };
                hoveredNoteId.value = id; hoveredNoteText.value = note.note; showNoteTooltip.value = true;
            }
        }
    }
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.relatedTarget as HTMLElement)?.closest('.note-hover-tooltip')) return;
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; };

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const handleClickOutside = (event: MouseEvent) => { if (showContextMenu.value) showContextMenu.value = false; };
const handleKeyDown = (event: KeyboardEvent) => { if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); } };

const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const cr = containerRef.value.getBoundingClientRect();
    const pct = ((e.clientX - cr.left) / cr.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
};
const stopResize = () => { isResizing.value = false; };

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <!-- Part Header -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400  px-4 py-2" style="background-color: #F1F2EC;">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header"
                v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions"
                v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">

                <!-- ═══════ LEFT – Passage ═══════ -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="px-6 py-4">
                        <h2 class="text-xl text-center font-bold">
                            <span data-segment-id="passage-title"
                                v-html="getHighlightedSegment('passage-title')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="p-4">
                            <div class="text-justify leading-relaxed text-gray-700">
                                <span data-segment-id="passage" class="text-base text-gray-700 select-text"
                                    v-html="getHighlightedPassage()"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center
                            border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ═══════ RIGHT – Questions ═══════ -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ══════════════════════════════
                                 Q27-30  Multiple Choice
                            ══════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q27-30-title" class="text-gray-700 select-text"
                                            v-html="getHighlightedSegment('q27-30-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700 select-text"
                                        data-segment-id="q27-30-inst1"
                                        v-html="getHighlightedSegment('q27-30-inst1')"></p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text"
                                        data-segment-id="q27-30-inst2"
                                        v-html="getHighlightedSegment('q27-30-inst2')"></p>
                                </div>

                                <!-- Q27 -->
                                <div id="question-27" class="relative bg-white p-2 mb-5"
                                    @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-start gap-2 mb-2">
                                        <span class="shrink-0 text-base font-bold text-gray-900">27.</span>
                                        <span data-segment-id="q27-text"
                                            class="text-base text-gray-900 select-text flex-1"
                                            v-html="getHighlightedSegment('q27-text')"></span>
                                    </div>
                                    <div class="ml-6 space-y-1">
                                        <label v-for="opt in ['A','B','C','D']" :key="opt"
                                            class="flex items-start gap-2 cursor-pointer">
                                            <input type="radio" :value="opt" v-model="answers.q27"
                                                class="mt-0.5 shrink-0 accent-gray-900" />
                                            <span class="text-sm text-gray-900 flex gap-1">
                                                <span class="font-bold shrink-0">{{ opt }}</span>
                                                <span :data-segment-id="`q27-opt${opt}`" class="select-text"
                                                    v-html="getHighlightedSegment(`q27-opt${opt}`)"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <button v-show="isBookmarkVisible(27)" @click.stop="toggleFlag(27)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q28 -->
                                <div id="question-28" class="relative bg-white p-2 mb-5"
                                    @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-start gap-2 mb-2">
                                        <span class="shrink-0 text-base font-bold text-gray-900">28.</span>
                                        <span data-segment-id="q28-text"
                                            class="text-base text-gray-900 select-text flex-1"
                                            v-html="getHighlightedSegment('q28-text')"></span>
                                    </div>
                                    <div class="ml-6 space-y-1">
                                        <label v-for="opt in ['A','B','C','D']" :key="opt"
                                            class="flex items-start gap-2 cursor-pointer">
                                            <input type="radio" :value="opt" v-model="answers.q28"
                                                class="mt-0.5 shrink-0 accent-gray-900" />
                                            <span class="text-sm text-gray-900 flex gap-1">
                                                <span class="font-bold shrink-0">{{ opt }}</span>
                                                <span :data-segment-id="`q28-opt${opt}`" class="select-text"
                                                    v-html="getHighlightedSegment(`q28-opt${opt}`)"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <button v-show="isBookmarkVisible(28)" @click.stop="toggleFlag(28)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q29 -->
                                <div id="question-29" class="relative bg-white p-2 mb-5"
                                    @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-start gap-2 mb-2">
                                        <span class="shrink-0 text-base font-bold text-gray-900">29.</span>
                                        <span data-segment-id="q29-text"
                                            class="text-base text-gray-900 select-text flex-1"
                                            v-html="getHighlightedSegment('q29-text')"></span>
                                    </div>
                                    <div class="ml-6 space-y-1">
                                        <label v-for="opt in ['A','B','C','D']" :key="opt"
                                            class="flex items-start gap-2 cursor-pointer">
                                            <input type="radio" :value="opt" v-model="answers.q29"
                                                class="mt-0.5 shrink-0 accent-gray-900" />
                                            <span class="text-sm text-gray-900 flex gap-1">
                                                <span class="font-bold shrink-0">{{ opt }}</span>
                                                <span :data-segment-id="`q29-opt${opt}`" class="select-text"
                                                    v-html="getHighlightedSegment(`q29-opt${opt}`)"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <button v-show="isBookmarkVisible(29)" @click.stop="toggleFlag(29)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Q30 -->
                                <div id="question-30" class="relative bg-white p-2 mb-5"
                                    @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                    <div class="flex items-start gap-2 mb-2">
                                        <span class="shrink-0 text-base font-bold text-gray-900">30.</span>
                                        <span data-segment-id="q30-text"
                                            class="text-base text-gray-900 select-text flex-1"
                                            v-html="getHighlightedSegment('q30-text')"></span>
                                    </div>
                                    <div class="ml-6 space-y-1">
                                        <label v-for="opt in ['A','B','C','D']" :key="opt"
                                            class="flex items-start gap-2 cursor-pointer">
                                            <input type="radio" :value="opt" v-model="answers.q30"
                                                class="mt-0.5 shrink-0 accent-gray-900" />
                                            <span class="text-sm text-gray-900 flex gap-1">
                                                <span class="font-bold shrink-0">{{ opt }}</span>
                                                <span :data-segment-id="`q30-opt${opt}`" class="select-text"
                                                    v-html="getHighlightedSegment(`q30-opt${opt}`)"></span>
                                            </span>
                                        </label>
                                    </div>
                                    <button v-show="isBookmarkVisible(30)" @click.stop="toggleFlag(30)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ══════════════════════════════════
     Q31-34  Drag & Drop Word Bank
══════════════════════════════════ -->
<div class="p-6">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900 mb-2">
            <span data-segment-id="q31-34-title" class="text-gray-700 select-text"
                v-html="getHighlightedSegment('q31-34-title')"></span>
        </h3>
        <p class="mb-1 text-sm leading-relaxed text-gray-700 select-text"
            data-segment-id="q31-34-inst1"
            v-html="getHighlightedSegment('q31-34-inst1')"></p>
        <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text"
            data-segment-id="q31-34-inst2"
            v-html="getHighlightedSegment('q31-34-inst2')"></p>
        <p class="mb-3 text-sm font-bold text-gray-900 select-text"
            data-segment-id="q31-34-heading"
            v-html="getHighlightedSegment('q31-34-heading')"></p>
    </div>

    <!-- Word bank -->
    <div class="mb-4 border border-gray-300 rounded p-3 bg-gray-50"
        @dragover="onDragOver31" @drop="onDropToBank31">
        <p class="text-xs font-bold text-gray-700 mb-2 select-none">
            A) inventive &nbsp;|&nbsp; B) similar &nbsp;|&nbsp; C) beneficial &nbsp;|&nbsp;
            D) next &nbsp;|&nbsp; E) mixed &nbsp;|&nbsp; F) justified &nbsp;|&nbsp; G) inferior
        </p>
        <div class="flex flex-wrap gap-2">
            <template v-for="letter in wordBankLetters" :key="letter">
                <div v-if="bankLetters31_34.includes(letter)" class="letter-chip"
                    draggable="true" @dragstart="onDragStartFromBank31($event, letter)">
                    {{ letter }}) {{ wordBankLabels[letter] }}
                </div>
                <div v-else class="letter-chip-placeholder"></div>
            </template>
        </div>
    </div>

    <!-- Summary with inline drop zones -->
    <div class="border border-gray-300 rounded p-4 bg-gray-50 text-base text-gray-900 leading-loose">
        <span data-segment-id="q31-pre" class="select-text"
            v-html="getHighlightedSegment('q31-pre')"></span>

        <!-- Q31 -->
        <span id="question-31" 
            class="inline-flex items-center align-middle relative mx-1"
            @mouseenter="hoveredQuestion = 31" 
            @mouseleave="hoveredQuestion = null">
            <span class="drop-zone" :class="{ 'drop-zone-filled': answers.q31 }"
                @dragover="onDragOver31" @drop="onDropToSlot31_34($event, 31)">
                <span v-if="answers.q31" class="letter-chip-placed" draggable="true"
                    @dragstart="onDragStartFromSlot31_34($event, answers.q31, 31)">
                    {{ answers.q31 }}) {{ wordBankLabels[answers.q31] }}
                </span>
                <span v-else class="drop-placeholder">31</span>
            </span>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 31 || isQuestionFlagged(31) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(31)"
                :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span data-segment-id="q31-post" class="select-text"
            v-html="getHighlightedSegment('q31-post')"></span>

        <!-- Q32 -->
        <span id="question-32" 
            class="inline-flex items-center align-middle relative mx-1"
            @mouseenter="hoveredQuestion = 32" 
            @mouseleave="hoveredQuestion = null">
            <span class="drop-zone" :class="{ 'drop-zone-filled': answers.q32 }"
                @dragover="onDragOver31" @drop="onDropToSlot31_34($event, 32)">
                <span v-if="answers.q32" class="letter-chip-placed" draggable="true"
                    @dragstart="onDragStartFromSlot31_34($event, answers.q32, 32)">
                    {{ answers.q32 }}) {{ wordBankLabels[answers.q32] }}
                </span>
                <span v-else class="drop-placeholder">32</span>
            </span>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 32 || isQuestionFlagged(32) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(32)"
                :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span data-segment-id="q32-post" class="select-text"
            v-html="getHighlightedSegment('q32-post')"></span>

        <!-- Q33 -->
        <span id="question-33" 
            class="inline-flex items-center align-middle relative mx-1"
            @mouseenter="hoveredQuestion = 33" 
            @mouseleave="hoveredQuestion = null">
            <span class="drop-zone" :class="{ 'drop-zone-filled': answers.q33 }"
                @dragover="onDragOver31" @drop="onDropToSlot31_34($event, 33)">
                <span v-if="answers.q33" class="letter-chip-placed" draggable="true"
                    @dragstart="onDragStartFromSlot31_34($event, answers.q33, 33)">
                    {{ answers.q33 }}) {{ wordBankLabels[answers.q33] }}
                </span>
                <span v-else class="drop-placeholder">33</span>
            </span>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 33 || isQuestionFlagged(33) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(33)"
                :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>

        <span data-segment-id="q33-post" class="select-text"
            v-html="getHighlightedSegment('q33-post')"></span>

        <!-- Q34 -->
        <span id="question-34" 
            class="inline-flex items-center align-middle relative mx-1"
            @mouseenter="hoveredQuestion = 34" 
            @mouseleave="hoveredQuestion = null">
            <span class="drop-zone" :class="{ 'drop-zone-filled': answers.q34 }"
                @dragover="onDragOver31" @drop="onDropToSlot31_34($event, 34)">
                <span v-if="answers.q34" class="letter-chip-placed" draggable="true"
                    @dragstart="onDragStartFromSlot31_34($event, answers.q34, 34)">
                    {{ answers.q34 }}) {{ wordBankLabels[answers.q34] }}
                </span>
                <span v-else class="drop-placeholder">34</span>
            </span>
            <button
                class="inline-flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0 ml-1 opacity-0 hover:opacity-100"
                :class="[
                    isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500 opacity-100' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                    hoveredQuestion === 34 || isQuestionFlagged(34) ? 'opacity-100' : 'opacity-0'
                ]"
                @click.stop="toggleFlag(34)"
                :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </button>
        </span>
    </div>
</div>

                            <!-- ══════════════════════════════
                                 Q35-40  Matching People
                                 Layout: identical to Q24-27 in doc3
                                 Questions (left bordered box) + sticky draggable options (right)
                            ══════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                                        <span data-segment-id="q35-40-title" class="text-gray-700 select-text"
                                            v-html="getHighlightedSegment('q35-40-title')"></span>
                                    </h3>
                                    <p class="mb-1 text-sm leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q35-40-inst1" class="select-text"
                                            v-html="getHighlightedSegment('q35-40-inst1')"></span><br />
                                        <span data-segment-id="q35-40-inst2" class="select-text"
                                            v-html="getHighlightedSegment('q35-40-inst2')"></span><br />
                                        <span data-segment-id="q35-40-inst3" class="select-text"
                                            v-html="getHighlightedSegment('q35-40-inst3')"></span><br />
                                        <strong data-segment-id="q35-40-nb" class="select-text text-gray-900"
                                            v-html="getHighlightedSegment('q35-40-nb')"></strong>
                                    </p>
                                </div>

                                <!-- Side-by-side: questions left, sticky options right — identical to Q24-27 doc3 -->
                                <div class="flex gap-6">

                                    <!-- LEFT: questions with drop zones on the right of each row -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                            <!-- Q35 -->
                                            <div id="question-35" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 35" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text shrink-0">35.</span>
                                                <div class="flex-1">
                                                    <span data-segment-id="q35-text" class="select-text"
                                                        v-html="getHighlightedSegment('q35-text')"></span>
                                                </div>
                                                <span class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 35 ? 'border-blue-500 bg-blue-50' : answers.q35 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver35($event, 35)"
                                                    @dragleave="handleDragLeave35"
                                                    @drop="handleDrop35($event, 35)"
                                                    @click="clearAnswer35(35)"
                                                    :title="answers.q35 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q35 ? getPeopleLabel(answers.q35) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(35)" @click.stop="toggleFlag(35)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q36 -->
                                            <div id="question-36" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text shrink-0">36.</span>
                                                <div class="flex-1">
                                                    <span data-segment-id="q36-text" class="select-text"
                                                        v-html="getHighlightedSegment('q36-text')"></span>
                                                </div>
                                                <span class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 36 ? 'border-blue-500 bg-blue-50' : answers.q36 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver35($event, 36)"
                                                    @dragleave="handleDragLeave35"
                                                    @drop="handleDrop35($event, 36)"
                                                    @click="clearAnswer35(36)"
                                                    :title="answers.q36 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q36 ? getPeopleLabel(answers.q36) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(36)" @click.stop="toggleFlag(36)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q37 -->
                                            <div id="question-37" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text shrink-0">37.</span>
                                                <div class="flex-1">
                                                    <span data-segment-id="q37-text" class="select-text"
                                                        v-html="getHighlightedSegment('q37-text')"></span>
                                                </div>
                                                <span class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver35($event, 37)"
                                                    @dragleave="handleDragLeave35"
                                                    @drop="handleDrop35($event, 37)"
                                                    @click="clearAnswer35(37)"
                                                    :title="answers.q37 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q37 ? getPeopleLabel(answers.q37) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(37)" @click.stop="toggleFlag(37)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q38 -->
                                            <div id="question-38" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text shrink-0">38.</span>
                                                <div class="flex-1">
                                                    <span data-segment-id="q38-text" class="select-text"
                                                        v-html="getHighlightedSegment('q38-text')"></span>
                                                </div>
                                                <span class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 38 ? 'border-blue-500 bg-blue-50' : answers.q38 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver35($event, 38)"
                                                    @dragleave="handleDragLeave35"
                                                    @drop="handleDrop35($event, 38)"
                                                    @click="clearAnswer35(38)"
                                                    :title="answers.q38 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q38 ? getPeopleLabel(answers.q38) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(38)" @click.stop="toggleFlag(38)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q39 -->
                                            <div id="question-39" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text shrink-0">39.</span>
                                                <div class="flex-1">
                                                    <span data-segment-id="q39-text" class="select-text"
                                                        v-html="getHighlightedSegment('q39-text')"></span>
                                                </div>
                                                <span class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 39 ? 'border-blue-500 bg-blue-50' : answers.q39 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver35($event, 39)"
                                                    @dragleave="handleDragLeave35"
                                                    @drop="handleDrop35($event, 39)"
                                                    @click="clearAnswer35(39)"
                                                    :title="answers.q39 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q39 ? getPeopleLabel(answers.q39) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(39)" @click.stop="toggleFlag(39)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Q40 -->
                                            <div id="question-40" class="relative flex items-start gap-3 pr-10"
                                                @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                                <span class="font-bold text-gray-900 select-text shrink-0">40.</span>
                                                <div class="flex-1">
                                                    <span data-segment-id="q40-text" class="select-text"
                                                        v-html="getHighlightedSegment('q40-text')"></span>
                                                </div>
                                                <span class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer shrink-0"
                                                    :class="dragOverQuestion === 40 ? 'border-blue-500 bg-blue-50' : answers.q40 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                    @dragover="handleDragOver35($event, 40)"
                                                    @dragleave="handleDragLeave35"
                                                    @drop="handleDrop35($event, 40)"
                                                    @click="clearAnswer35(40)"
                                                    :title="answers.q40 ? 'Click to clear' : 'Drop answer here'">
                                                    {{ answers.q40 ? getPeopleLabel(answers.q40) : '' }}
                                                </span>
                                                <button v-show="isBookmarkVisible(40)" @click.stop="toggleFlag(40)"
                                                    class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all shrink-0"
                                                    :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- RIGHT: sticky draggable people options (always available, reusable) -->
                                    <div class="w-52 shrink-0 self-start sticky top-4">
                                        <p class="mb-2 text-xs font-bold text-gray-700">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1">
                                                <div v-for="opt in peopleOptions" :key="opt.value"
                                                    class="flex cursor-grab items-center gap-1.5 rounded border border-gray-300 px-2 py-1.5 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedItem === opt.value && dragSource === 'bank35' }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart35($event, opt.value)"
                                                    @dragend="handleDragEnd35">
                                                    <span class="font-bold text-gray-900 text-xs shrink-0">{{ opt.value }}</span>
                                                    <span class="text-gray-700 text-xs leading-tight">{{ opt.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /flex gap-6 -->
                            </div>

                        </div>
                    </div>
                </div><!-- /answer-panel -->
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         Teleported overlays
    ══════════════════════════════════════════════════════ -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
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

        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip"
                        class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                    "{{ selectedText }}"
                </p>
                <input v-model="noteInputText" type="text" spellcheck="false" autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote" @keyup.escape="cancelNote" />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote"
                    class="border-2 border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-text { user-select: text; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text; }
.select-none { user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; cursor: col-resize; }

.passage-panel { width: 100%; }
.answer-panel { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel { width: calc(100% - var(--panel-width) - 0.5rem); }
}

/* ── Q31-34 word bank drag & drop ──────────────────────────────────────────── */
.letter-chip {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0 0.6rem; height: 2rem;
    border: 2px solid #374151; border-radius: 4px;
    background: #fff; font-weight: 700; font-size: 0.875rem; color: #111827;
    cursor: grab; user-select: none; white-space: nowrap;
    transition: background 0.15s, box-shadow 0.15s;
}
.letter-chip:active { cursor: grabbing; }
.letter-chip:hover { background: #f3f4f6; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }

.letter-chip-placeholder {
    display: inline-flex; width: 2rem; height: 2rem;
    border: 2px dashed #d1d5db; border-radius: 4px; background: transparent;
}

.drop-zone {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 7rem; height: 2rem;
    border: 2px dashed #9ca3af; border-radius: 4px; background: #f9fafb;
    transition: border-color 0.15s, background 0.15s; vertical-align: middle;
}
.drop-zone:hover { border-color: #374151; background: #f3f4f6; }
.drop-zone-filled { border-style: solid; border-color: #374151; background: #fff; }

.drop-placeholder { font-size: 0.65rem; color: #9ca3af; letter-spacing: 0.02em; pointer-events: none; }

.letter-chip-placed {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0 0.5rem; height: 1.8rem;
    font-weight: 700; font-size: 0.875rem; color: #111827;
    cursor: grab; user-select: none; white-space: nowrap;
}
.letter-chip-placed:active { cursor: grabbing; }

/* ── Highlight marks ───────────────────────────────────────────────────────── */
mark[data-highlight-id] {
    padding: 2px 0; border-radius: 2px; cursor: pointer; color: inherit;
    -webkit-background-clip: padding-box !important; background-clip: padding-box !important;
}
.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0,0,0,0.1); border-radius: 4px;
    padding: 2px 4px; margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}
@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.05); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

/* Scrollbar */
.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

/* Tooltips */
.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-bottom: 8px solid white; filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db; z-index: -1;
}
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0;
    border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn { color: #9ca3af; }
.note-hover-tooltip .note-delete-btn:hover { color: #ef4444; }
</style>

<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147, 197, 253, 0.7) !important; }
.question-input::placeholder { font-weight: 700; color: #374151; }
</style>