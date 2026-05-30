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

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-dinosaur-part3-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// Q27-33: heading matching (drag-drop)
// Q34-36: sentence completion (text input)
// Q37-40: matching features (dropdown select A-H)
const answers = ref({
    q27: '', q28: '', q29: '', q30: '', q31: '', q32: '', q33: '',
    q34: '', q35: '', q36: '',
    q37: '', q38: '', q39: '', q40: '',
});

// Drag and drop for Q27-33
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: '165 million years' },
    { value: 'ii',   label: 'The body plan of archosaurs' },
    { value: 'iii',  label: 'Dinosaurs - terrible lizards' },
    { value: 'iv',   label: 'Classification according to pelvic anatomy' },
    { value: 'v',    label: 'The suborders of Saurischia' },
    { value: 'vi',   label: 'Lizards and dinosaurs - two distinct superorders' },
    { value: 'vii',  label: 'Unique body plan helps identify dinosaurs from other animals' },
    { value: 'viii', label: 'Herbivore dinosaurs' },
    { value: 'ix',   label: 'Lepidosaurs' },
    { value: 'x',    label: 'Frills and shelves' },
    { value: 'xi',   label: 'The origins of dinosaurs and lizards' },
    { value: 'xii',  label: 'Bird-hipped dinosaurs' },
    { value: 'xiii', label: 'Skull bones distinguish dinosaurs from other archosaurs' },
];

const availableHeadingOptions = computed(() => {
    const usedOptions = [
        answers.value.q27, answers.value.q28, answers.value.q29, answers.value.q30,
        answers.value.q31, answers.value.q32, answers.value.q33,
    ].filter(Boolean);
    return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
});

const handleHeadingDragStart = (e: DragEvent, option: string) => {
    draggedHeading.value = option;
    if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', option); }
};
const handleHeadingDragEnd = () => { draggedHeading.value = null; dragOverQuestion.value = null; };
const handleHeadingDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    dragOverQuestion.value = questionNum;
};
const handleHeadingDragLeave = () => { dragOverQuestion.value = null; };
const handleHeadingDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedHeading.value;
    if (option) { const key = `q${questionNum}` as keyof typeof answers.value; answers.value[key] = option; }
    draggedHeading.value = null; dragOverQuestion.value = null;
};
const clearHeadingAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};
const getHeadingLabel = (value: string): string => {
    const option = headingOptions.find((opt) => opt.value === value);
    return option ? `${option.value} – ${option.label}` : '';
};

// Q37-40 feature options
const featureOptions = [
    { value: 'A', label: 'are both divided into two orders' },
    { value: 'B', label: 'the former had a "fully improved gait"' },
    { value: 'C', label: 'were not usually very heavy' },
    { value: 'D', label: 'could walk or run on their back legs' },
    { value: 'E', label: 'their hind limbs sprawled out to the sides' },
    { value: 'F', label: 'walked or ran on four legs rather than two' },
    { value: 'G', label: 'both had a pelvic girdle comprising six bones' },
    { value: 'H', label: 'did not always eat meat' },
];

// Separate drag-drop state for features (Q37-40)
const draggedFeature = ref<string | null>(null);
const dragOverFeatureQuestion = ref<number | null>(null);

const availableFeatureOptions = computed(() => {
    const usedOptions = [answers.value.q37, answers.value.q38, answers.value.q39, answers.value.q40].filter(Boolean);
    return featureOptions.filter((opt) => !usedOptions.includes(opt.value));
});

const handleFeatureDragStart = (e: DragEvent, option: string) => {
    draggedFeature.value = option;
    if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', option); }
};
const handleFeatureDragEnd = () => { draggedFeature.value = null; dragOverFeatureQuestion.value = null; };
const handleFeatureDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    dragOverFeatureQuestion.value = questionNum;
};
const handleFeatureDragLeave = () => { dragOverFeatureQuestion.value = null; };
const handleFeatureDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedFeature.value;
    if (option) { const key = `q${questionNum}` as keyof typeof answers.value; answers.value[key] = option; }
    draggedFeature.value = null; dragOverFeatureQuestion.value = null;
};
const clearFeatureAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};
const getFeatureLabel = (value: string): string => {
    const option = featureOptions.find((opt) => opt.value === value);
    return option ? `${option.value} – ${option.label}` : '';
};

// ===== PASSAGE PARAGRAPHS =====
const paragraphA = `Although the name dinosaur is derived from the Greek for "terrible lizard", dinosaurs were not, in fact, lizards at all. Like lizards, dinosaurs are included in the class Reptilia, or reptiles, one of the five main classes of Vertebrata, animals with backbones. However, at the next level of classification, within reptiles, significant differences in the skeletal anatomy of lizards and dinosaurs have led scientists to place these groups of animals into two different superorders: Lepidosauria or lepidosaurs, and Archosauria, or archosaurs.`;

const paragraphB = `Classified as lepidosaurs are lizards and snakes and their prehistoric ancestors. Included among the archosaurs, or "ruling reptiles", are prehistoric and modern crocodiles, and the now extinct thecodonts, pterosaurs and dinosaurs. Palaeontologists believe that both dinosaurs and crocodiles evolved, in the later years of the Triassic Period (c. 248-208 million years ago), from creatures called pseudosuchian thecodonts. Lizards, snakes and different types of thecodont are believed to have evolved earlier  the Triassic Period from reptiles known as eosuchians.`;

const paragraphC = `The most important skeletal differences between dinosaurs and other archosaurs are in the bones of the skull, pelvis and limbs. Dinosaur skulls are found in a great range of shapes and sizes, reflecting the different eating habits and lifestyles of a large and varied group of animals that dominated life on Earth for an extraordinary 165 million years. However, unlike the skulls of any other known animals, the skulls of dinosaurs had two long bones known as vomers. These bones extended on either side of the head, from the front of the snout to the level of the holes on the skull known as the antorbital fenestra, situated in front of the dinosaur's orbits or eye sockets.`;

const paragraphD = `All dinosaurs, whether large or small, quadrupedal or bipedal, Peet-footed or slow- moving, shared a common body plan. Identification of this plan makes it possible to differentiate dinosaurs from any other types of animal, even other archosaurs. Most significantly, in dinosaurs, the pelvis and femur had evolved so that the hind limbs were held vertically beneath the body, rather than sprawling out to the sides like the limbs of a lizard. The femur of a dinosaur had a sharply in-turned neck and a ball-shaped head, which slotted into a fully open acetabulum or hip socket. A supra-acetabular crest helped prevent dislocation of the femur. The position of the knee joint, aligned below the acetabulum, made it possible for the whole hind limb to swing backwards and forwards. This unique combination of features gave dinosaurs what is known as a "fully improved gait". Evolution of this highly efficient method of walking also developed in mammals, but among reptiles it occurred only in dinosaurs.`;

const paragraphE = `For the purpose of further classification, dinosaurs are divided into two orders: Saurischia or saurischian dinosaurs, and Ornithischia, or ornithischian dinosaurs. This division is made on the basis of their pelvic anatomy. All dinosaurs had a pelvic girdle with each side comprised of three bones: the pubis, ilium and ischium. However, the orientation of these bones follows one of two patterns. In saurischian dinosaurs, also known as lizard-hipped dinosaurs, the pubis points forwards, as is usual in most types of reptile. By contrast, in ornithischian, or bird-hipped, dinosaurs, the pubis points back- wards towards the rear of the animal, which is also true of birds.`;

const paragraphF = `Of the two orders of dinosaurs, the Saurischia was the larger and the first to evolve. It is divided into two suborders: Therapoda, or therapods, and Sauropodomorpha or sauropodomorphs. The therapods, or "beast feet", were bipedal, predatory carnivores. They ranged in size from the mighty Tyrannosaurus rex, 12m long, 5.6m tall and weighing an estimated 6.4 tonnes, to the smallest known dinosaur, Compsognathus, a mere 1.4m long and estimated 3kg in weight when fully grown. The sauropodomorphs, or "lizard feet forms", included both bipedal and quadrupedal dinosaurs. Some sauropodomorphs were carnivorous or omnivorous but later species were typically herbivorous. They included some of the largest and best-known of all dinosaurs, such as Diplodocus, a huge quadruped with an elephant-like body, a long, thin tail and neck that gave it a total length of 27m, and a tiny head.`;

const paragraphG = `Ornithischian dinosaurs were bipedal or quadrupedal herbivores. They are now usually divided into three suborders: Ornithopoda, Thyreophora and Marginocephalia. The ornithopods, or "bird feet", both large and small, could walk or run on their long hind legs, balancing their body by holding their tails stiffly off the ground behind them. An example is lguanodon, up to 9m long, 5m tall and weighing 4.5 tonnes. The thyreophorans, or "shield bearers", also known as armoured dinosaurs, were quadrupeds with rows of protective bony spikes, studs, or plates along their backs and tails. They included Stegosaurus, 9m long and weighing 2 tonnes.`;

const paragraphH = `The marginocephalians or "margined heads", were bipedal or quadrupedal ornithischians with a deep bony frill or narrow shelf at the back of the skull. An example is Triceratops, a rhinoceros-like dinosaur, 9m long, weighing 5.4 tonnes and bearing a prominent neck frill and three large horns.`;

const passageText = `What is a dinosaur?

<b>A. </b>${paragraphA}

<b>B. </b>${paragraphB}

<b>C. </b>${paragraphC}

<b>D. </b>${paragraphD}

<b>E. </b>${paragraphE}

<b>F. </b>${paragraphF}

<b>G. </b>${paragraphG}

<b>H. </b>${paragraphH}`;

const textSegments = ref([
    { id: 'part3-header', text: 'Reading Passage 3', offset: 0 },
    { id: 'part3-instruction', text: 'You should spend about 20 minutes on Questions 27–40 which are based on Reading Passage 3 below.', offset: 18 },
    { id: 'passage-title', text: 'What is a dinosaur?', offset: 115 },
    { id: 'passage', text: passageText, offset: 200 },
    // paragraph segments — exact offsets
    { id: 'para-a', text: paragraphA, offset: 223 },
    { id: 'para-b', text: paragraphB, offset: 770 },
    { id: 'para-c', text: paragraphC, offset: 1338 },
    { id: 'para-d', text: paragraphD, offset: 2019 },
    { id: 'para-e', text: paragraphE, offset: 3025 },
    { id: 'para-f', text: paragraphF, offset: 3705 },
    { id: 'para-g', text: paragraphG, offset: 4589 },
    { id: 'para-h', text: paragraphH, offset: 5235 },
    // Q27-33 section
    { id: 'q27-33-title', text: 'Questions 27–33', offset: 6000 },
    { id: 'q27-33-inst1', text: 'Reading Passage 3 has eight paragraphs A–H.', offset: 6016 },
    { id: 'q27-33-inst2', text: 'Choose the most suitable heading for each paragraph from the list of headings below.', offset: 6060 },
    { id: 'q27-33-inst3', text: 'Write the appropriate numbers i–xiii in boxes 27–33 on your answer sheet.', offset: 6145 },
    { id: 'headings-title', text: 'List of headings', offset: 6218 },
    { id: 'para-27-label', text: 'Paragraph A', offset: 6235 },
    { id: 'para-28-label', text: 'Paragraph B', offset: 6247 },
    { id: 'para-29-label', text: 'Paragraph C', offset: 6259 },
    { id: 'para-30-label', text: 'Paragraph D', offset: 6271 },
    { id: 'para-31-label', text: 'Paragraph E', offset: 6283 },
    { id: 'para-32-label', text: 'Paragraph F', offset: 6295 },
    { id: 'para-33-label', text: 'Paragraph G', offset: 6307 },
    // Q34-36 sentence completion
    { id: 'q34-36-title', text: 'Questions 34–36', offset: 6400 },
    { id: 'q34-36-inst1', text: 'Complete the sentences below with NO MORE THAN THREE WORDS from the passage for each answer.', offset: 6416 },
    { id: 'q34-36-inst2', text: 'Write your answers in boxes 34–36 on your answer sheet.', offset: 6508 },
    { id: 'q34-stem', text: 'Lizards and dinosaurs are classified into two different superorders because of the difference in their', offset: 6564 },
    { id: 'q35-stem', text: 'In the Triassic Period,', offset: 6670 },
    { id: 'q35-suffix', text: 'evolved into thecodonts, for example, lizards and snakes.', offset: 6698 },
    { id: 'q36-stem', text: 'Dinosaur skulls differed from those of any other known animals because of the presence of vomers:', offset: 6756 },
    // Q37-40 matching features
    { id: 'q37-40-title', text: 'Questions 37–40', offset: 6860 },
    { id: 'q37-40-inst1', text: 'Choose one phrase A–H from the list of features to match with the dinosaurs listed below.', offset: 6876 },
    { id: 'q37-40-inst2', text: 'Write the appropriate letters A–H in boxes 37–40 on your answer sheet.', offset: 6966 },
    { id: 'q37-40-nb', text: 'NB   You may use each phrase once only.', offset: 7037 },
    { id: 'q37-stem', text: '37.  Dinosaurs differed from lizards, because ...', offset: 7100 },
    { id: 'q38-stem', text: '38.  Saurischian and ornithischian dinosaurs ...', offset: 7150 },
    { id: 'q39-stem', text: '39.  Unlike therapods, sauropodomorphs ...', offset: 7198 },
    { id: 'q40-stem', text: '40.  Some dinosaurs used their tails to balance, others ...', offset: 7240 },
]);

const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;
    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') htmlIndex++;
            htmlIndex++;
        } else { plainIndex++; htmlIndex++; }
    }
    return htmlIndex;
};

const getPlainTextLength = (htmlText: string): number => htmlText.replace(/<[^>]*>/g, '').length;

const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);
    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
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

const getHighlightedSegmentById = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';
    return getHighlightedSegment(segment.text);
};

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

const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) { showContextMenu.value = false; return; }
        const selected = selection.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }
        const range = selection.getRangeAt(0);

        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) container = container.parentNode as Node;
            const segmentEl = (container as Element).closest('[data-segment-id]');
            if (!segmentEl) return null;
            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;
            let offsetInSegment = 0;
            const treeWalker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let currentNode;
            while ((currentNode = treeWalker.nextNode())) {
                if (currentNode === node) { offsetInSegment += offsetInNode; break; }
                else offsetInSegment += currentNode.textContent?.length || 0;
            }
            return segment.offset + offsetInSegment;
        };

        let startAbsOffset = getAbsoluteOffset(range.startContainer, range.startOffset);
        let endAbsOffset = getAbsoluteOffset(range.endContainer, range.endOffset);
        if (startAbsOffset === null || endAbsOffset === null) { showContextMenu.value = false; window.getSelection()?.removeAllRanges(); return; }
        if (startAbsOffset > endAbsOffset) [startAbsOffset, endAbsOffset] = [endAbsOffset, startAbsOffset];

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
        } else { showContextMenu.value = false; }
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
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => { document.querySelector<HTMLInputElement>('.note-input-field')?.focus(); }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 3' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((note) => note.id !== id); };

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
    if (noteMark) { showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = ''; }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false; hoveredNoteId.value = null; hoveredNoteText.value = '';
    }
};

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

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
    } else { closeDeleteTooltip(); }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
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
    <div
        ref="contentTextRef"
        @mouseup="handleContentTextSelect"
        @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32"
    >
        <!-- Header -->
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part3-header" v-html="getHighlightedSegmentById('part3-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part3-instruction" v-html="getHighlightedSegmentById('part3-instruction')"></p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ===== READING PASSAGE ===== -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-shrink-0 border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 select-text">
                            <span data-segment-id="passage-title" v-html="getHighlightedSegmentById('passage-title')"></span>
                        </h2>
                    </div>

                    <div class="flex-1 space-y-6 overflow-y-auto p-6" :style="contentZoom">
                        <div class="space-y-4 text-base leading-relaxed select-text sm:text-base">

                            <!-- Paragraph A with drop zone -->
                            <div class="px-4">
                                <div id="question-27" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 27)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 27)" @click="clearHeadingAnswer(27)"
                                        :title="answers.q27 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q27" class="font-medium">{{ getHeadingLabel(answers.q27) }}</span>
                                        <span v-else class="font-bold text-gray-500">27</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 27)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 27 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">A. </span><span class="select-text" data-segment-id="para-a" v-html="getHighlightedSegment(paragraphA)"></span>
                                </div>
                            </div>

                            <!-- Paragraph B with drop zone -->
                            <div class="px-4">
                                <div id="question-28" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 28 ? 'border-blue-500 bg-blue-50' : answers.q28 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 28)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 28)" @click="clearHeadingAnswer(28)"
                                        :title="answers.q28 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q28" class="font-medium">{{ getHeadingLabel(answers.q28) }}</span>
                                        <span v-else class="font-bold text-gray-500">28</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 28)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 28 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">B. </span><span class="select-text" data-segment-id="para-b" v-html="getHighlightedSegment(paragraphB)"></span>
                                </div>
                            </div>

                            <!-- Paragraph C with drop zone -->
                            <div class="px-4">
                                <div id="question-29" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 29 ? 'border-blue-500 bg-blue-50' : answers.q29 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 29)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 29)" @click="clearHeadingAnswer(29)"
                                        :title="answers.q29 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q29" class="font-medium">{{ getHeadingLabel(answers.q29) }}</span>
                                        <span v-else class="font-bold text-gray-500">29</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 29)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(29) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 29 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">C. </span><span class="select-text" data-segment-id="para-c" v-html="getHighlightedSegment(paragraphC)"></span>
                                </div>
                            </div>

                            <!-- Paragraph D with drop zone -->
                            <div class="px-4">
                                <div id="question-30" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 30 ? 'border-blue-500 bg-blue-50' : answers.q30 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 30)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 30)" @click="clearHeadingAnswer(30)"
                                        :title="answers.q30 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q30" class="font-medium">{{ getHeadingLabel(answers.q30) }}</span>
                                        <span v-else class="font-bold text-gray-500">30</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 30)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(30) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 30 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">D. </span><span class="select-text" data-segment-id="para-d" v-html="getHighlightedSegment(paragraphD)"></span>
                                </div>
                            </div>

                            <!-- Paragraph E with drop zone -->
                            <div class="px-4">
                                <div id="question-31" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 31" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 31 ? 'border-blue-500 bg-blue-50' : answers.q31 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 31)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 31)" @click="clearHeadingAnswer(31)"
                                        :title="answers.q31 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q31" class="font-medium">{{ getHeadingLabel(answers.q31) }}</span>
                                        <span v-else class="font-bold text-gray-500">31</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 31)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(31) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 31 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">E. </span><span class="select-text" data-segment-id="para-e" v-html="getHighlightedSegment(paragraphE)"></span>
                                </div>
                            </div>

                            <!-- Paragraph F with drop zone -->
                            <div class="px-4">
                                <div id="question-32" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 32" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 32 ? 'border-blue-500 bg-blue-50' : answers.q32 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 32)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 32)" @click="clearHeadingAnswer(32)"
                                        :title="answers.q32 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q32" class="font-medium">{{ getHeadingLabel(answers.q32) }}</span>
                                        <span v-else class="font-bold text-gray-500">32</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 32)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(32) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 32 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">F. </span><span class="select-text" data-segment-id="para-f" v-html="getHighlightedSegment(paragraphF)"></span>
                                </div>
                            </div>

                            <!-- Paragraph G with drop zone -->
                            <div class="px-4">
                                <div id="question-33" class="mb-2 flex items-center gap-2"
                                    @mouseenter="hoveredQuestion = 33" @mouseleave="hoveredQuestion = null">
                                    <span
                                        class="flex-1 flex items-center justify-center min-h-9 border-2 border-dashed rounded px-3 py-1 text-sm text-center transition-all cursor-pointer"
                                        :class="dragOverQuestion === 33 ? 'border-blue-500 bg-blue-50' : answers.q33 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-500'"
                                        @dragover="handleHeadingDragOver($event, 33)" @dragleave="handleHeadingDragLeave"
                                        @drop="handleHeadingDrop($event, 33)" @click="clearHeadingAnswer(33)"
                                        :title="answers.q33 ? 'Click to clear' : 'Drop heading here'">
                                        <span v-if="answers.q33" class="font-medium">{{ getHeadingLabel(answers.q33) }}</span>
                                        <span v-else class="font-bold text-gray-500">33</span>
                                    </span>
                                    <button @click.stop="emit('toggleFlag', 33)"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(33) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 33 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                        :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                </div>
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">G. </span><span class="select-text" data-segment-id="para-g" v-html="getHighlightedSegment(paragraphG)"></span>
                                </div>
                            </div>

                            <!-- Paragraph H (no drop zone — Q27-33 only covers A-G) -->
                            <div class="px-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="font-bold">H. </span><span class="select-text" data-segment-id="para-h" v-html="getHighlightedSegment(paragraphH)"></span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ===== QUESTIONS PANEL ===== -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- ===== Q27-33: Drag-drop heading matching ===== -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q27-33-title" v-html="getHighlightedSegmentById('q27-33-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5">
                                        <span data-segment-id="q27-33-inst1" v-html="getHighlightedSegmentById('q27-33-inst1')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5">
                                        <span data-segment-id="q27-33-inst2" v-html="getHighlightedSegmentById('q27-33-inst2')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 italic mb-4">
                                        <span data-segment-id="q27-33-inst3" v-html="getHighlightedSegmentById('q27-33-inst3')"></span>
                                    </p>
                                </div>

                                <!-- Heading cards -->
                                <div class="mb-2">
                                    <h4 class="text-sm font-bold text-gray-900 mb-3">
                                        <span data-segment-id="headings-title" v-html="getHighlightedSegmentById('headings-title')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <div
                                            v-for="option in availableHeadingOptions"
                                            :key="option.value"
                                            class="flex items-center gap-3 border border-gray-300 bg-white px-3 py-2.5 cursor-grab hover:bg-gray-50 transition-colors active:cursor-grabbing"
                                            :class="{ 'opacity-40': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd"
                                        >
                                            <span class="text-gray-400 text-base leading-none shrink-0 font-bold tracking-tighter">=</span>
                                            <span class="font-bold text-gray-900 text-sm shrink-0 w-8">{{ option.value }}</span>
                                            <span class="text-sm text-gray-700">{{ option.label }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== Q34-36: Sentence Completion — one paragraph ===== -->
                            <div class="p-6">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q34-36-title" v-html="getHighlightedSegmentById('q34-36-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5">
                                        <span data-segment-id="q34-36-inst1" v-html="getHighlightedSegmentById('q34-36-inst1')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-4">
                                        <span data-segment-id="q34-36-inst2" v-html="getHighlightedSegmentById('q34-36-inst2')"></span>
                                    </p>
                                </div>

                                <!-- Single bordered paragraph with all 3 sentences flowing together -->
                                <div class="border border-gray-300 p-4 text-sm text-gray-800 leading-loose">
                                    <span class="inline-flex flex-wrap items-baseline gap-x-1 gap-y-2">

                                        <!-- Q34 -->
                                        <span
                                            id="question-34"
                                            class="relative inline-flex flex-wrap items-baseline gap-x-1"
                                            @mouseenter="hoveredQuestion = 34"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                @click.stop="emit('toggleFlag', 34)"
                                                class="absolute -top-1 -right-1 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(34) ? 'border-red-400 text-red-500' : 'border-gray-400 text-gray-500'"
                                                :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <span class="select-text" data-segment-id="q34-stem" v-html="getHighlightedSegmentById('q34-stem')"></span>
                                            <input
                                                v-model="answers.q34"
                                                type="text" spellcheck="false" autocomplete="off"
                                                placeholder="34"
                                                class="question-input min-w-[150px] border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                            /><span class="text-gray-400">.</span>
                                        </span>

                                        <!-- Q35 -->
                                        <span
                                            id="question-35"
                                            class="relative inline-flex flex-wrap items-baseline gap-x-1"
                                            @mouseenter="hoveredQuestion = 35"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                @click.stop="emit('toggleFlag', 35)"
                                                class="absolute -top-1 -right-1 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(35) ? 'border-red-400 text-red-500' : 'border-gray-400 text-gray-500'"
                                                :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <span class="select-text" data-segment-id="q35-stem" v-html="getHighlightedSegmentById('q35-stem')"></span>
                                            <input
                                                v-model="answers.q35"
                                                type="text" spellcheck="false" autocomplete="off"
                                                placeholder="35"
                                                class="question-input min-w-[130px] border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                            />
                                            <span class="select-text" data-segment-id="q35-suffix" v-html="getHighlightedSegmentById('q35-suffix')"></span>
                                        </span>

                                        <!-- Q36 -->
                                        <span
                                            id="question-36"
                                            class="relative inline-flex flex-wrap items-baseline gap-x-1"
                                            @mouseenter="hoveredQuestion = 36"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                @click.stop="emit('toggleFlag', 36)"
                                                class="absolute -top-1 -right-1 z-10 flex h-5 w-5 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500' : 'border-gray-400 text-gray-500'"
                                                :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <span class="select-text" data-segment-id="q36-stem" v-html="getHighlightedSegmentById('q36-stem')"></span>
                                            <input
                                                v-model="answers.q36"
                                                type="text" spellcheck="false" autocomplete="off"
                                                placeholder="36"
                                                class="question-input min-w-[150px] border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none"
                                            />
                                        </span>

                                    </span>
                                </div>
                            </div>

                            <!-- ===== Q37-40: Matching Features — side by side ===== -->
                            <div class="p-6">
                                <div class="mb-3">
                                    <h3 class="text-base font-bold text-gray-900 mb-1 select-text">
                                        <span data-segment-id="q37-40-title" v-html="getHighlightedSegmentById('q37-40-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-0.5 select-text">
                                        <span data-segment-id="q37-40-inst1" v-html="getHighlightedSegmentById('q37-40-inst1')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1 select-text">
                                        <span data-segment-id="q37-40-inst2" v-html="getHighlightedSegmentById('q37-40-inst2')"></span>
                                    </p>
                                    <p class="text-sm text-gray-600 italic mb-4 select-text">
                                        <span data-segment-id="q37-40-nb" v-html="getHighlightedSegmentById('q37-40-nb')"></span>
                                    </p>
                                </div>

                                <!-- Side-by-side: feature list LEFT, drop zones RIGHT -->
                                <div class="flex gap-4 items-start">

                                    <!-- Left: Draggable feature cards -->
                                    <div class="w-1/2 shrink-0">
                                        <h4 class="text-sm font-bold text-gray-900 mb-2">List of features</h4>
                                        <div class="space-y-1">
                                            <div
                                                v-for="option in availableFeatureOptions"
                                                :key="option.value"
                                                class="flex items-center gap-2 border border-gray-300 bg-white px-2.5 py-2 cursor-grab hover:bg-gray-50 transition-colors active:cursor-grabbing"
                                                :class="{ 'opacity-40': draggedFeature === option.value }"
                                                draggable="true"
                                                @dragstart="handleFeatureDragStart($event, option.value)"
                                                @dragend="handleFeatureDragEnd"
                                            >
                                                <span class="text-gray-400 text-sm leading-none shrink-0 font-bold">=</span>
                                                <span class="font-bold text-gray-900 text-sm shrink-0 w-5">{{ option.value }}</span>
                                                <span class="text-sm text-gray-700 leading-tight">{{ option.label }}</span>
                                            </div>
                                            <!-- Placeholder when all used -->
                                            <p v-if="availableFeatureOptions.length === 0" class="text-sm text-gray-400 italic p-2">All features placed</p>
                                        </div>
                                    </div>

                                    <!-- Right: Q37-40 drop zones -->
                                    <div class="flex-1 space-y-2">

                                        <!-- Q37 -->
                                        <div
                                            id="question-37"
                                            class="relative"
                                            @mouseenter="hoveredQuestion = 37"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                @click.stop="emit('toggleFlag', 37)"
                                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <p class="text-sm text-gray-900 mb-1 pr-7">
                                                <span class="select-text" data-segment-id="q37-stem" v-html="getHighlightedSegmentById('q37-stem')"></span>
                                            </p>
                                            <span
                                                class="flex items-center justify-center min-h-8 border-2 border-dashed rounded px-2 py-1 text-sm text-center transition-all cursor-pointer w-full"
                                                :class="dragOverFeatureQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-400'"
                                                @dragover="handleFeatureDragOver($event, 37)"
                                                @dragleave="handleFeatureDragLeave"
                                                @drop="handleFeatureDrop($event, 37)"
                                                @click="clearFeatureAnswer(37)"
                                                :title="answers.q37 ? 'Click to clear' : 'Drop feature here'"
                                            >
                                                <span v-if="answers.q37" class="font-medium">{{ getFeatureLabel(answers.q37) }}</span>
                                                <span v-else class="font-bold text-gray-400">—</span>
                                            </span>
                                        </div>

                                        <!-- Q38 -->
                                        <div
                                            id="question-38"
                                            class="relative"
                                            @mouseenter="hoveredQuestion = 38"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                                @click.stop="emit('toggleFlag', 38)"
                                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(38) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                                :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <p class="text-sm text-gray-900 mb-1 pr-7">
                                                <span class="select-text" data-segment-id="q38-stem" v-html="getHighlightedSegmentById('q38-stem')"></span>
                                            </p>
                                            <span
                                                class="flex items-center justify-center min-h-8 border-2 border-dashed rounded px-2 py-1 text-sm text-center transition-all cursor-pointer w-full"
                                                :class="dragOverFeatureQuestion === 38 ? 'border-blue-500 bg-blue-50' : answers.q38 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-400'"
                                                @dragover="handleFeatureDragOver($event, 38)"
                                                @dragleave="handleFeatureDragLeave"
                                                @drop="handleFeatureDrop($event, 38)"
                                                @click="clearFeatureAnswer(38)"
                                                :title="answers.q38 ? 'Click to clear' : 'Drop feature here'"
                                            >
                                                <span v-if="answers.q38" class="font-medium">{{ getFeatureLabel(answers.q38) }}</span>
                                                <span v-else class="font-bold text-gray-400">—</span>
                                            </span>
                                        </div>

                                        <!-- Q39 -->
                                        <div
                                            id="question-39"
                                            class="relative"
                                            @mouseenter="hoveredQuestion = 39"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                                @click.stop="emit('toggleFlag', 39)"
                                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(39) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                                :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <p class="text-sm text-gray-900 mb-1 pr-7">
                                                <span class="select-text" data-segment-id="q39-stem" v-html="getHighlightedSegmentById('q39-stem')"></span>
                                            </p>
                                            <span
                                                class="flex items-center justify-center min-h-8 border-2 border-dashed rounded px-2 py-1 text-sm text-center transition-all cursor-pointer w-full"
                                                :class="dragOverFeatureQuestion === 39 ? 'border-blue-500 bg-blue-50' : answers.q39 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-400'"
                                                @dragover="handleFeatureDragOver($event, 39)"
                                                @dragleave="handleFeatureDragLeave"
                                                @drop="handleFeatureDrop($event, 39)"
                                                @click="clearFeatureAnswer(39)"
                                                :title="answers.q39 ? 'Click to clear' : 'Drop feature here'"
                                            >
                                                <span v-if="answers.q39" class="font-medium">{{ getFeatureLabel(answers.q39) }}</span>
                                                <span v-else class="font-bold text-gray-400">—</span>
                                            </span>
                                        </div>

                                        <!-- Q40 -->
                                        <div
                                            id="question-40"
                                            class="relative"
                                            @mouseenter="hoveredQuestion = 40"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <button
                                                v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                                @click.stop="emit('toggleFlag', 40)"
                                                class="absolute top-1 right-1 z-10 flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(40) ? 'border-red-400 text-red-500 opacity-100' : 'border-gray-400 text-gray-500 opacity-100'"
                                                :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                            <p class="text-sm text-gray-900 mb-1 pr-7">
                                                <span class="select-text" data-segment-id="q40-stem" v-html="getHighlightedSegmentById('q40-stem')"></span>
                                            </p>
                                            <span
                                                class="flex items-center justify-center min-h-8 border-2 border-dashed rounded px-2 py-1 text-sm text-center transition-all cursor-pointer w-full"
                                                :class="dragOverFeatureQuestion === 40 ? 'border-blue-500 bg-blue-50' : answers.q40 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white text-gray-400'"
                                                @dragover="handleFeatureDragOver($event, 40)"
                                                @dragleave="handleFeatureDragLeave"
                                                @drop="handleFeatureDrop($event, 40)"
                                                @click="clearFeatureAnswer(40)"
                                                :title="answers.q40 ? 'Click to clear' : 'Drop feature here'"
                                            >
                                                <span v-if="answers.q40" class="font-medium">{{ getFeatureLabel(answers.q40) }}</span>
                                                <span v-else class="font-bold text-gray-400">—</span>
                                            </span>
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
                        <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                            </svg>
                            <span class="mt-1 text-sm font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            <span class="mt-1 text-sm font-medium text-gray-700">Highlight</span>
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
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span class="mt-1 text-sm font-medium text-gray-700">Delete</span>
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
        </Teleport>

        <!-- Note Input Modal -->
        <Teleport to="body">
            <div v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-sm text-gray-600 italic">"{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" placeholder="Write your note here..."
                        class="note-input-field w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote" />
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="rounded border border-gray-300 bg-white px-3 py-0.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
                    <button @click="saveNote" class="rounded bg-gray-900 px-3 py-0.5 text-sm font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
                </div>
            </div>
        </Teleport>
    </div>
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
mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; }
.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }
.highlight-question { animation: highlightPulse 2s ease-in-out; }
@keyframes highlightPulse {
    0% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50% { background-color: rgba(0,0,0,0.2); transform: scale(1.02); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}
.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }
.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-bottom: 8px solid white; filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-bottom: 9px solid #d1d5db; z-index: -1;
}
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent;
    border-top: 8px solid white; filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    width: 0; height: 0; border-left: 9px solid transparent; border-right: 9px solid transparent;
    border-top: 9px solid #d1d5db; z-index: -1;
}
.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-icon { color: #6b7280; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn { color: #9ca3af; }
.question-input::placeholder { font-weight: 700; color: #374151; }
</style>

<style>
.note-highlight { border-bottom: 2px solid rgba(0,0,0,0.4); cursor: help; position: relative; display: inline; }
.note-highlight::after { content: '📝'; display: inline-block; margin-left: 2px; font-size: 0.7em; vertical-align: super; line-height: 0; opacity: 0.9; pointer-events: none; }
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>
