

<!--<script setup lang="ts">
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
const PANEL_WIDTH_KEY = 'reading-ielts002-part1-panel-width';
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

// Drag and drop functionality for questions 1-6
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i', label: 'Not identifying the correct priorities' },
    { value: 'ii', label: 'A solution for the long term' },
    { value: 'iii', label: 'The difficulty of changing your mind' },
    { value: 'iv', label: 'Why looking back is unhelpful' },
    { value: 'v', label: 'Strengthening inner resources' },
    { value: 'vi', label: 'A successful approach to the study of decision-making' },
    { value: 'vii', label: 'The danger of trusting a global market' },
    { value: 'viii', label: 'Reluctance to go beyond the familiar' },
    { value: 'ix', label: 'The power of the first number' },
    { value: 'x', label: 'The need for more effective risk assessment' },
    { value: 'xi', label: 'Underestimating the difficulties ahead' },
];

// Filter out used heading options (questions 1-6 map to q1-q6)
const availableHeadingOptions = computed(() => {
    const usedOptions = [
        answers.value.q1,
        answers.value.q2,
        answers.value.q3,
        answers.value.q4,
        answers.value.q5,
        answers.value.q6,
    ].filter(Boolean);
    return headingOptions.filter((opt) => !usedOptions.includes(opt.value));
});

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

// paragraph texts for A-F (split from passage)
const paragraphA = `The works of Aboriginal artists are now much in demand throughout the world, and not just in Australia, where they are already fully recognised: the National Museum of Australia, which opened in Canberra in 2001, designated 40% of its exhibition space to works by Aborigines. In Europe their art is being exhibited at a museum in Lyon. France, while the future Quai Branly museum in Paris - which will be devoted to arts and civilisations of Africa. Asia, Oceania and the Americas - plans to commission frescoes by artists from Australia.`;

const paragraphB = `Their artistic movement began about 30 years ago. but its roots go back to time immemorial. All the works refer to the founding myth of the Aboriginal culture, 'the Dreaming'. That internal geography, which is rendered with a brush and colours, is also the expression of the Aborigines' long quest to regain the land which was stolen from them when Europeans arrived in the nineteenth century. 'Painting is nothing without history.' says one such artist. Michael Nelson Tjakamarra.`;

const paragraphC = `There arc now fewer than 400.000 Aborigines living in Australia. They have been swamped by the country's 17.5 million immigrants. These original 'natives' have been living in Australia for 50.000 years, but they were undoubtedly maltreated by the newcomers. Driven back to the most barren lands or crammed into slums on the outskirts of cities, the Aborigines were subjected to a policy of 'assimilation', which involved kidnapping children to make them better 'integrated' into European society, and herding the nomadic Aborigines by force into settled communities.`;

const paragraphD = `It was in one such community, Papunya, near Alice Springs, in the central desert, that Aboriginal painting first came into its own. In 1971, a white schoolteacher. Geoffrey Bardon, suggested to a group of Aborigines that they should decorate the school walls with ritual motifs. so as to pass on to the younger generation the myths that were starting to fade from their collective memory, lie gave them brushes. colours and surfaces to paint on cardboard and canvases. He was astounded by the result. But their art did not come like a bolt from the blue: for thousands of years Aborigines had been 'painting' on the ground using sands of different colours, and on rock faces. They had also been decorating their bodies for ceremonial purposes. So there existed a formal vocabulary.`;

const paragraphE = `This had already been noted by Europeans. In the early twentieth century. Aboriginal communities brought together by missionaries in northern Australia had been encouraged to reproduce on tree bark the motifs found on rock faces. Artists turned out a steady stream of works, supported by the churches, which helped to sell them to the public, and between 1950 and I960 Aboriginal paintings began to reach overseas museums. Painting on bark persisted in the north, whereas the communities in the central desert increasingly used acrylic paint, and elsewhere in Western Australia women explored the possibilities of wax painting and dyeing processes, known as 'batik'.`;

const paragraphF = `What Aborigines depict are always elements of the Dreaming, the collective history that each community is both part of and guardian of. I he Dreaming is the story of their origins, of their 'Great Ancestors', who passed on their knowledge, their art and their skills (hunting, medicine, painting, music and dance) to man. 'The Dreaming is not synonymous with the moment when the world was created.' says Stephane Jacob, one of the organisers of the Lyon exhibition. 'For Aborigines, that moment has never ceased to exist. It is perpetuated by the cycle of the seasons and the religious ceremonies which the Aborigines organise. Indeed the aim of those ceremonies is also to ensure the permanence of that golden age. The central function of Aboriginal painting, even in its contemporary manifestations, is to guarantee the survival of this world. The Dreaming is both past, present and future.'`;

const paragraphG = `Each work is created individually, with a form peculiar to each artist, but it is created within and on behalf of a community who must approve it. An artist cannot use a 'dream' that does not belong to his or her community, since each community is the owner of its dreams, just as it is anchored to a territory marked out by its ancestors, so each painting can be interpreted as a kind of spiritual road map for that community.`;

const paragraphH = `Nowadays, each community is organised as a cooperative and draws on the services of an art adviser, a government-employed agent who provides the artists with materials, deals with galleries and museums and redistributes the proceeds from sales among the artists. Today, Aboriginal painting has become a great success. Some works sell for more than $25,000, and exceptional items may fetch as much as $180,000 in Australia.`;

const paragraphI = `'By exporting their paintings as though they were surfaces of their territory, by accompanying them to the temples of western art. the Aborigines have redrawn the map of their country, into whose depths they were exiled,* says Yves Le Fur. of the Quai Branlv museum. 'Masterpieces have been created. Their undeniable power prompts a dialogue that has proved all too rare in the history of contacts between the two cultures'.`;

const passageText = `A.
People make terrible decisions about the future. The evidence is all around, from their investments in the stock markets to the way they run their businesses. In fact, people are consistently bad at dealing with uncertainty, underestimating some kinds of risk and overestimating others. Surely there must be a better way than using intuition?
B.
In the 1960s a young American research psychologist, Daniel Kahneman, became interested in people's inability to make logical decisions. That launched him on a career to show just how irrationally people behave in practice. When Kahneman and his colleagues first started work, the idea of applying psychological insights to economics and business decisions was seen as rather bizarre. But in the past decade the fields of behavioural finance and behavioural economics have blossomed, and in 2002 Kahneman shared a Nobel prize in economics for his work. Today he is in demand by business organizations and international banking companies. But, he says, there are plenty of institutions that still fail to understand the roots of their poor decisions. He claims that, far from being random, these mistakes are systematic and predictable.
C.
One common cause of problems in decision-making is over-optimism. Ask most people about the future, and they will see too much blue sky ahead, even if past experience suggests otherwise. Surveys have shown that people's forecasts of future stock market movements are far more optimistic than past long-term returns would justify. The same goes for their hopes of ever-rising prices for their homes or doing well in games of chance. Such optimism can be useful for managers or sportsmen, and sometimes turns into a self-fulfilling prophecy. But most of the time it results in wasted effort and dashed hopes. Kahneman's work points to three types of over-confidence. First, people tend to exaggerate their own skill and prowess; in polls, far fewer than half the respondents admit to having below-average skills in, say, driving. Second, they overestimate the amount of control they have over the future, forgetting about luck and chalking up success solely to skill. And third, in competitive pursuits such as dealing on shares, they forget that they have to judge their skills against those of the competition.
D.
Another source of wrong decisions is related to the decisive effect of the initial meeting, particularly in negotiations over money. This is referred to as the 'anchor effect'. Once a figure has been mentioned, it takes a strange hold over the human mind. The asking price quoted in a house sale, for example, tends to become accepted by all parties as the 'anchor' around which negotiations take place. Much the same goes for salary negotiations or mergers and acquisitions.If nobody has much information to go on, a figure can provide comfort - even though it may lead to a terrible mistake.
E.
In addition, mistakes may arise due to stubbornness. No one likes to abandon a cherished belief, and the earlier a decision has been taken, the harder it is to abandon it. Drug companies must decide early to cancel a failing research project to avoid wasting money, but may find it difficult to admit they have made a mistake. In the same way, analysts may have become wedded early to a single explanation that coloured their perception. A fresh eye always helps.
F.
People also tend to put a lot of emphasis on things they have seen and experienced themselves, which may not be the best guide to decision-making. For example, somebody may buy an overvalued share because a relative has made thousands on it, only to get his fingers burned. In finance, too much emphasis on information close at hand helps to explain the tendency by most investors to invest only within the country they live in. Even though they know that diversification is good for their portfolio, a large majority of both Americans and Europeans invest far too heavily in the shares of their home countries. They would be much better off spreading their risks more widely.
G.
More information is helpful in making any decision but, says Kahneman, people spend proportionally too much time on small decisions and not enough on big ones. They need to adjust the balance. During the boom years, some companies put as much effort into planning their office party as into considering strategic mergers.
H.
Finally, crying over spilled milk is not just a waste of time; it also often colours people's perceptions of the future. Some stock market investors trade far too frequently because they are chasing the returns on shares they wish they had bought earlier.
I.
Kahneman reckons that some types of businesses are much better than others at dealing with risk. Pharmaceutical companies, which are accustomed to many failures and a few big successes in their drug-discovery programmes, are airly rational about their risk-taking. But banks, he says, have a long way to go. They may take big risks on a few huge loans, but are extremely cautious about their much more numerous loans to small businesses, many of which may be less risky than the big ones. And the research has implications for governments too. They face a whole range of sometimes conflicting political pressures, which means they are even more likely to take irrational decisions.`;


// ─── TEXT SEGMENTS ────────────────────────────────────────────────────────────
// All segments that appear in the QUESTIONS panel (the only panel we keep).
// Offsets must be unique and non-overlapping so highlights resolve correctly.
// We build them with a running counter so they are always consistent.

const buildSegments = () => {
    const raw: { id: string; text: string }[] = [
        // Questions 1-6 header + instructions
        { id: 'q1-6-title',        text: 'Questions 1-6' },
        { id: 'q1-6-instruction1', text: 'Reading Passage 1 has nine paragraphs A-I. Choose the correct heading for paragraphs B, D, E, F, G & H from the list of headings below.' },
        { id: 'q1-6-instruction2', text: 'Write the correct number (i–xi) in boxes 1-6 on your answer sheet.' },
        { id: 'list-headings-title', text: 'List of Headings' },

        // The 11 headings
        { id: 'heading-i',    text: 'i. Not identifying the correct priorities' },
        { id: 'heading-ii',   text: 'ii. A solution for the long term' },
        { id: 'heading-iii',  text: 'iii. The difficulty of changing your mind' },
        { id: 'heading-iv',   text: 'iv. Why looking back is unhelpful' },
        { id: 'heading-v',    text: 'v. Strengthening inner resources' },
        { id: 'heading-vi',   text: 'vi. A successful approach to the study of decision-making' },
        { id: 'heading-vii',  text: 'vii. The danger of trusting a global market' },
        { id: 'heading-viii', text: 'viii. Reluctance to go beyond the familiar' },
        { id: 'heading-ix',   text: 'ix. The power of the first number' },
        { id: 'heading-x',    text: 'x. The need for more effective risk assessment' },
        { id: 'heading-xi',   text: 'xi. Underestimating the difficulties ahead' },

        // paragraph labels shown next to drop zones
        { id: 'para-label-a', text: paragraphA },
        { id: 'para-label-b', text: paragraphB },
        { id: 'para-label-c', text: paragraphC },
        { id: 'para-label-d', text: paragraphD },
        { id: 'para-label-e', text: paragraphE },
        { id: 'para-label-f', text: paragraphF },
        { id: 'para-label-g', text: paragraphG },
        { id: 'para-label-h', text: paragraphH },
        { id: 'para-label-i', text: paragraphI },

        // Questions 7-10 header + instructions
        { id: 'q7-10-title',       text: 'Questions 7-10' },
        { id: 'q7-10-instruction',  text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 7–10 on your answer sheet.' },

        // Q7
        { id: 'q7-text', text: "People initially found Kahneman's work unusual because he" },
        { id: 'q7-a',    text: 'A. saw mistakes as following predictable patterns.' },
        { id: 'q7-b',    text: 'B. was unaware of behavioural approaches.' },
        { id: 'q7-c',    text: 'C. dealt with irrational types of practice.' },
        { id: 'q7-d',    text: 'D. applied psychology to finance and economics.' },

        // Q8
        { id: 'q8-text', text: "The writer mentions house-owners' attitudes towards the value of their homes to illustrate that" },
        { id: 'q8-a',    text: 'A. past failures may destroy an optimistic attitude.' },
        { id: 'q8-b',    text: 'B. people tend to exaggerate their chances of success.' },
        { id: 'q8-c',    text: 'C. optimism may be justified in certain circumstances.' },
        { id: 'q8-d',    text: 'D. people are influenced by the success of others.' },

        // Q9
        { id: 'q9-text', text: 'Stubbornness and inflexibility can cause problems when people' },
        { id: 'q9-a',    text: 'A. think their financial difficulties are just due to bad luck.' },
        { id: 'q9-b',    text: 'B. avoid seeking advice from experts and analysts.' },
        { id: 'q9-c',    text: 'C. refuse to invest in the early stages of a project.' },
        { id: 'q9-d',    text: 'D. are unwilling to give up unsuccessful activities or beliefs.' },

        // Q10
        { id: 'q10-text', text: 'Why do many Americans and Europeans fail to spread their financial risks when investing?' },
        { id: 'q10-a',    text: 'A. They feel safer dealing in a context which is close to home.' },
        { id: 'q10-b',    text: 'B. They do not understand the benefits of diversification.' },
        { id: 'q10-c',    text: 'C. They are over-influenced by the successes of their relatives.' },
        { id: 'q10-d',    text: "D. They do not have sufficient knowledge of one another's countries." },

        // Questions 11-13 header + instructions
        { id: 'q11-13-title',       text: 'Questions 11–13' },
        { id: 'q11-13-instruction',  text: 'Answer the questions below, using NO MORE THAN THREE WORDS for each answer. Write your answers in boxes 11–13 on your answer sheet.' },

        { id: 'q11-text', text: 'Which two occupations may benefit from being over-optimistic?' },
        { id: 'q12-text', text: 'Which practical skill are many people over-confident about?' },
        { id: 'q13-text', text: 'Which type of business has a generally good attitude to dealing with uncertainty?' },
    ];

    let offset = 0;
    return raw.map((s) => {
        const seg = { id: s.id, text: s.text, offset };
        offset += s.text.length + 1; // +1 to avoid adjacent-segment collisions
        return seg;
    });
};

const textSegments = ref(buildSegments());

// ─── HIGHLIGHT HELPERS ────────────────────────────────────────────────────────

const getPlainTextLength = (htmlText: string): number =>
    htmlText.replace(/<[^>]*>/g, '').length;

const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;
    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') htmlIndex++;
            htmlIndex++;
        } else {
            plainIndex++;
            htmlIndex++;
        }
    }
    return htmlIndex;
};

const getHighlightedSegment = (segmentId: string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segment.text);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segment.text;
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
        })),
    ].sort((a, b) => b.start - a.start);

    let result = segment.text;

    for (const annotation of annotations) {
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

// ─── EXPOSE ───────────────────────────────────────────────────────────────────

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

// ─── SELECTION / HIGHLIGHT HANDLERS ──────────────────────────────────────────

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

            const segmentId = segmentEl.getAttribute('data-segment-id') || '';
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

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
    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start),
    );
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
    notes.value.push({
        id: Date.now(),
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
        part: 'Part 1',
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
            const note = notes.value.find((n) => n.id === parseInt(noteId, 10));
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
    if (target.closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

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

const closeContextMenu = () => { showContextMenu.value = false; };
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
    if (event.key === 'Escape' && showContextMenu.value) showContextMenu.value = false;
};

// Resize handlers
const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const newLeftWidth = ((event.clientX - containerRect.left) / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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
</script>*/-->

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

// ── Resize ────────────────────────────────────────────────────────────────────
const PANEL_WIDTH_KEY = 'reading-ielts002-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// ── Highlight ─────────────────────────────────────────────────────────────────
const contentTextRef = ref<HTMLDivElement | null>(null);
const contextMenuPosition = ref({ x: 0, y: 0 });
const showContextMenu = ref(false);

const { highlights, selectedText, selectionRange, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// ── Notes ─────────────────────────────────────────────────────────────────────
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);

// ── Delete tooltip ────────────────────────────────────────────────────────────
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// ── Note hover tooltip ────────────────────────────────────────────────────────
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// ── Answers ───────────────────────────────────────────────────────────────────
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

// ── Drag & Drop ───────────────────────────────────────────────────────────────
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const headingOptions = [
    { value: 'i',    label: 'Not identifying the correct priorities' },
    { value: 'ii',   label: 'A solution for the long term' },
    { value: 'iii',  label: 'The difficulty of changing your mind' },
    { value: 'iv',   label: 'Why looking back is unhelpful' },
    { value: 'v',    label: 'Strengthening inner resources' },
    { value: 'vi',   label: 'A successful approach to the study of decision-making' },
    { value: 'vii',  label: 'The danger of trusting a global market' },
    { value: 'viii', label: 'Reluctance to go beyond the familiar' },
    { value: 'ix',   label: 'The power of the first number' },
    { value: 'x',    label: 'The need for more effective risk assessment' },
    { value: 'xi',   label: 'Underestimating the difficulties ahead' },
];

// q1=ParaB, q2=ParaD, q3=ParaE, q4=ParaF, q5=ParaG, q6=ParaH
const availableHeadingOptions = computed(() => {
    const used = [answers.value.q1, answers.value.q2, answers.value.q3,
                  answers.value.q4, answers.value.q5, answers.value.q6].filter(Boolean);
    return headingOptions.filter((opt) => !used.includes(opt.value));
});

const handleHeadingDragStart = (e: DragEvent, option: string) => {
    draggedHeading.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
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
    if (option) {
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
const getHeadingLabel = (value: string): string =>
    headingOptions.find((opt) => opt.value === value)?.label ?? '';

// ── Passage paragraphs ────────────────────────────────────────────────────────
const paragraphA = `A. People make terrible decisions about the future. The evidence is all around, from their investments in the stock markets to the way they run their businesses. In fact, people are consistently bad at dealing with uncertainty, underestimating some kinds of risk and overestimating others. Surely there must be a better way than using intuition?`;

const paragraphB = `B. In the 1960s a young American research psychologist, Daniel Kahneman, became interested in people's inability to make logical decisions. That launched him on a career to show just how irrationally people behave in practice. When Kahneman and his colleagues first started work, the idea of applying psychological insights to economics and business decisions was seen as rather bizarre. But in the past decade the fields of behavioural finance and behavioural economics have blossomed, and in 2002 Kahneman shared a Nobel prize in economics for his work. Today he is in demand by business organizations and international banking companies. But, he says, there are plenty of institutions that still fail to understand the roots of their poor decisions. He claims that, far from being random, these mistakes are systematic and predictable.`;

const paragraphC = `C. One common cause of problems in decision-making is over-optimism. Ask most people about the future, and they will see too much blue sky ahead, even if past experience suggests otherwise. Surveys have shown that people's forecasts of future stock market movements are far more optimistic than past long-term returns would justify. The same goes for their hopes of ever-rising prices for their homes or doing well in games of chance. Such optimism can be useful for managers or sportsmen, and sometimes turns into a self-fulfilling prophecy. But most of the time it results in wasted effort and dashed hopes. Kahneman's work points to three types of over-confidence. First, people tend to exaggerate their own skill and prowess; in polls, far fewer than half the respondents admit to having below-average skills in, say, driving. Second, they overestimate the amount of control they have over the future, forgetting about luck and chalking up success solely to skill. And third, in competitive pursuits such as dealing on shares, they forget that they have to judge their skills against those of the competition.`;

const paragraphD = `D. Another source of wrong decisions is related to the decisive effect of the initial meeting, particularly in negotiations over money. This is referred to as the 'anchor effect'. Once a figure has been mentioned, it takes a strange hold over the human mind. The asking price quoted in a house sale, for example, tends to become accepted by all parties as the 'anchor' around which negotiations take place. Much the same goes for salary negotiations or mergers and acquisitions. If nobody has much information to go on, a figure can provide comfort — even though it may lead to a terrible mistake.`;

const paragraphE = `E. In addition, mistakes may arise due to stubbornness. No one likes to abandon a cherished belief, and the earlier a decision has been taken, the harder it is to abandon it. Drug companies must decide early to cancel a failing research project to avoid wasting money, but may find it difficult to admit they have made a mistake. In the same way, analysts may have become wedded early to a single explanation that coloured their perception. A fresh eye always helps.`;

const paragraphF = `F. People also tend to put a lot of emphasis on things they have seen and experienced themselves, which may not be the best guide to decision-making. For example, somebody may buy an overvalued share because a relative has made thousands on it, only to get his fingers burned. In finance, too much emphasis on information close at hand helps to explain the tendency by most investors to invest only within the country they live in. Even though they know that diversification is good for their portfolio, a large majority of both Americans and Europeans invest far too heavily in the shares of their home countries. They would be much better off spreading their risks more widely.`;

const paragraphG = `G. More information is helpful in making any decision but, says Kahneman, people spend proportionally too much time on small decisions and not enough on big ones. They need to adjust the balance. During the boom years, some companies put as much effort into planning their office party as into considering strategic mergers.`;

const paragraphH = `H. Finally, crying over spilled milk is not just a waste of time; it also often colours people's perceptions of the future. Some stock market investors trade far too frequently because they are chasing the returns on shares they wish they had bought earlier.`;

const paragraphI = `I. Kahneman reckons that some types of businesses are much better than others at dealing with risk. Pharmaceutical companies, which are accustomed to many failures and a few big successes in their drug-discovery programmes, are fairly rational about their risk-taking. But banks, he says, have a long way to go. They may take big risks on a few huge loans, but are extremely cautious about their much more numerous loans to small businesses, many of which may be less risky than the big ones. And the research has implications for governments too. They face a whole range of sometimes conflicting political pressures, which means they are even more likely to take irrational decisions.`;

// ── Text segments (all selectable text, with auto-computed offsets) ────────────
const buildSegments = () => {
    const raw: { id: string; text: string }[] = [
        // Passage title + instruction
        { id: 'passage-title',   text: 'Why risks can go wrong – Human intuition is a bad guide to handling risk' },
        { id: 'passage-label',   text: 'Part 1' },
        // Paragraphs (left panel)
        { id: 'para-a', text: paragraphA },
        { id: 'para-b', text: paragraphB },
        { id: 'para-c', text: paragraphC },
        { id: 'para-d', text: paragraphD },
        { id: 'para-e', text: paragraphE },
        { id: 'para-f', text: paragraphF },
        { id: 'para-g', text: paragraphG },
        { id: 'para-h', text: paragraphH },
        { id: 'para-i', text: paragraphI },
        // Questions panel headings
        { id: 'q1-6-title',          text: 'Questions 1-6' },
        { id: 'q1-6-instruction1',   text: 'Reading Passage 1 has nine paragraphs A-I. Choose the correct heading for Paragraphs B, D, E, F, G & H from the list of headings below.' },
        { id: 'q1-6-instruction2',   text: 'Write the correct number (i–xi) in boxes 1-6 on your answer sheet.' },
        { id: 'list-headings-title', text: 'List of Headings' },
        { id: 'heading-i',    text: 'i. Not identifying the correct priorities' },
        { id: 'heading-ii',   text: 'ii. A solution for the long term' },
        { id: 'heading-iii',  text: 'iii. The difficulty of changing your mind' },
        { id: 'heading-iv',   text: 'iv. Why looking back is unhelpful' },
        { id: 'heading-v',    text: 'v. Strengthening inner resources' },
        { id: 'heading-vi',   text: 'vi. A successful approach to the study of decision-making' },
        { id: 'heading-vii',  text: 'vii. The danger of trusting a global market' },
        { id: 'heading-viii', text: 'viii. Reluctance to go beyond the familiar' },
        { id: 'heading-ix',   text: 'ix. The power of the first number' },
        { id: 'heading-x',    text: 'x. The need for more effective risk assessment' },
        { id: 'heading-xi',   text: 'xi. Underestimating the difficulties ahead' },
        // Q7-10
        { id: 'q7-10-title',       text: 'Questions 7-10' },
        { id: 'q7-10-instruction', text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 7–10 on your answer sheet.' },
        { id: 'q7-text', text: "People initially found Kahneman's work unusual because he" },
        { id: 'q7-a',    text: 'A. saw mistakes as following predictable patterns.' },
        { id: 'q7-b',    text: 'B. was unaware of behavioural approaches.' },
        { id: 'q7-c',    text: 'C. dealt with irrational types of practice.' },
        { id: 'q7-d',    text: 'D. applied psychology to finance and economics.' },
        { id: 'q8-text', text: "The writer mentions house-owners' attitudes towards the value of their homes to illustrate that" },
        { id: 'q8-a',    text: 'A. past failures may destroy an optimistic attitude.' },
        { id: 'q8-b',    text: 'B. people tend to exaggerate their chances of success.' },
        { id: 'q8-c',    text: 'C. optimism may be justified in certain circumstances.' },
        { id: 'q8-d',    text: 'D. people are influenced by the success of others.' },
        { id: 'q9-text', text: 'Stubbornness and inflexibility can cause problems when people' },
        { id: 'q9-a',    text: 'A. think their financial difficulties are just due to bad luck.' },
        { id: 'q9-b',    text: 'B. avoid seeking advice from experts and analysts.' },
        { id: 'q9-c',    text: 'C. refuse to invest in the early stages of a project.' },
        { id: 'q9-d',    text: 'D. are unwilling to give up unsuccessful activities or beliefs.' },
        { id: 'q10-text', text: 'Why do many Americans and Europeans fail to spread their financial risks when investing?' },
        { id: 'q10-a',   text: 'A. They feel safer dealing in a context which is close to home.' },
        { id: 'q10-b',   text: 'B. They do not understand the benefits of diversification.' },
        { id: 'q10-c',   text: 'C. They are over-influenced by the successes of their relatives.' },
        { id: 'q10-d',   text: "D. They do not have sufficient knowledge of one another's countries." },
        // Q11-13
        { id: 'q11-13-title',       text: 'Questions 11–13' },
        { id: 'q11-13-instruction', text: 'Answer the questions below, using NO MORE THAN THREE WORDS for each answer. Write your answers in boxes 11–13 on your answer sheet.' },
        { id: 'q11-text', text: 'Which two occupations may benefit from being over-optimistic?' },
        { id: 'q12-text', text: 'Which practical skill are many people over-confident about?' },
        { id: 'q13-text', text: 'Which type of business has a generally good attitude to dealing with uncertainty?' },
    ];

    let offset = 0;
    return raw.map((s) => {
        const seg = { id: s.id, text: s.text, offset };
        offset += s.text.length + 1;
        return seg;
    });
};

const textSegments = ref(buildSegments());

// ── Highlight helpers ─────────────────────────────────────────────────────────
const getPlainTextLength = (html: string) => html.replace(/<[^>]*>/g, '').length;

const plainToHtmlOffset = (html: string, plainOffset: number): number => {
    let p = 0, h = 0;
    while (p < plainOffset && h < html.length) {
        if (html[h] === '<') { while (h < html.length && html[h] !== '>') h++; h++; }
        else { p++; h++; }
    }
    return h;
};

const getHighlightedSegment = (segmentId: string): string => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const { offset: segmentOffset, text } = segment;
    const segmentPlainLen = getPlainTextLength(text);
    const segmentEnd = segmentOffset + segmentPlainLen;

    const overlappingHighlights = highlights.value.filter(
        (h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset,
    );
    const overlappingNotes = notes.value.filter(
        (n) => n.start < segmentEnd && n.end > segmentOffset,
    );

    if (!overlappingHighlights.length && !overlappingNotes.length) return text;

    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    let result = text;
    for (const ann of annotations) {
        const plainStart = Math.max(0, ann.start - segmentOffset);
        const plainEnd   = Math.min(segmentPlainLen, ann.end - segmentOffset);
        if (plainStart >= plainEnd) continue;
        const hStart = plainToHtmlOffset(result, plainStart);
        const hEnd   = plainToHtmlOffset(result, plainEnd);
        const before = result.substring(0, hStart);
        const mid    = result.substring(hStart, hEnd);
        const after  = result.substring(hEnd);
        if (ann.type === 'note') {
            result = `${before}<mark class="highlight-${ann.color} note-highlight" data-note-id="${ann.id}">${mid}</mark>${after}`;
        } else {
            result = `${before}<mark class="highlight-${ann.color}" data-highlight-id="${ann.id}">${mid}</mark>${after}`;
        }
    }
    return result;
};

// ── Public API ────────────────────────────────────────────────────────────────
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

// ── Selection handler ─────────────────────────────────────────────────────────
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

            const segmentId = segmentEl.getAttribute('data-segment-id') || '';
            const segment   = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) return null;

            let offsetInSegment = 0;
            const walker = document.createTreeWalker(segmentEl, NodeFilter.SHOW_TEXT);
            let cur: Node | null;
            while ((cur = walker.nextNode())) {
                if (cur === node) { offsetInSegment += offsetInNode; break; }
                offsetInSegment += cur.textContent?.length ?? 0;
            }
            return segment.offset + offsetInSegment;
        };

        let start = getAbsoluteOffset(range.startContainer, range.startOffset);
        let end   = getAbsoluteOffset(range.endContainer, range.endOffset);
        if (start === null || end === null) { showContextMenu.value = false; window.getSelection()?.removeAllRanges(); return; }
        if (start > end) [start, end] = [end, start];

        const rect = range.getBoundingClientRect();
        if (rect && (rect.width > 0 || rect.height > 0)) {
            const menuX = rect.left + rect.width / 2;
            const menuY = rect.top - 78;
            const vw    = window.innerWidth;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, 80), vw - 80),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value   = true;
            selectedText.value      = selection.toString();
            selectionRange.value    = { start, end };
        } else {
            showContextMenu.value = false;
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter(
        (n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start),
    );
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    noteInputPosition.value = { x: contextMenuPosition.value.x, y: contextMenuPosition.value.y + 60 };
    showNoteInput.value   = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: newStart, end: newEnd } = selectionRange.value;
    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value,
        note: noteInputText.value.trim(), start: newStart, end: newEnd, part: 'Part 1' });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote  = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote  = (id: number) => { notes.value = notes.value.filter((n) => n.id !== id); };

const handleNoteMouseEnter = (event: MouseEvent) => {
    const noteMark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement | null;
    if (!noteMark) return;
    const note = notes.value.find((n) => n.id === parseInt(noteMark.getAttribute('data-note-id')!, 10));
    if (!note) return;
    const rect = noteMark.getBoundingClientRect();
    let y = rect.top - 58;
    if (y < 10) y = rect.bottom + 8;
    noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
    hoveredNoteId.value   = note.id;
    hoveredNoteText.value = note.note;
    showNoteTooltip.value = true;
};

const handleNoteMouseLeave = (event: MouseEvent) => {
    if ((event.target as HTMLElement).closest('mark.note-highlight[data-note-id]')) {
        showNoteTooltip.value = false;
        hoveredNoteId.value   = null;
        hoveredNoteText.value = '';
    }
};

const handleTooltipMouseLeave = () => { showNoteTooltip.value = false; };

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) { deleteHighlight(highlightToDelete.value); closeDeleteTooltip(); }
};

const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        showNoteTooltip.value = false;
        hoveredNoteId.value   = null;
        hoveredNoteText.value = '';
    }
};

const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleContentClick = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement | null;
    if (mark) {
        event.stopPropagation();
        const rect = mark.getBoundingClientRect();
        deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
        highlightToDelete.value = parseInt(mark.getAttribute('data-highlight-id')!, 10);
        showDeleteTooltip.value = true;
    } else {
        closeDeleteTooltip();
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') showContextMenu.value = false;
};

// ── Resize ────────────────────────────────────────────────────────────────────
const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const w    = ((e.clientX - rect.left) / rect.width) * 100;
    if (w >= 20 && w <= 80) leftPanelWidth.value = w;
};
const stopResize = () => { isResizing.value = false; };

watch(leftPanelWidth, (v) => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

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
        <div class="border mx-5 mt-4 border-gray-400 part-header-box px-4 py-2">
            <p class="text-lg font-semibold uppercase tracking-widest text-gray-500 select-text"
                            data-segment-id="passage-label"
                            v-html="getHighlightedSegment('passage-label')">
                        </p>
            <p class="text-sm text-gray-700 select-text">Read the text and answer questions 1–13.</p>
        </div>

        <div class="mx-auto p-3 sm:p-4 lg:p-6">
            <div
                ref="containerRef"
                class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }"
            >

                <!-- ══════════════════════════════════════════════════════
                     LEFT PANEL — Passage with inline drop zones
                ══════════════════════════════════════════════════════ -->
                <div
                    class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ width: `${leftPanelWidth}%` }" @mouseup="handleContentTextSelect"
                >
                    <!-- Passage header -->
                    <div class="flex-shrink-0 border-b border-gray-200 p-4">

                        <h2 class="mt-1 text-lg font-bold text-gray-900 select-text"
                            data-segment-id="passage-title"
                            v-html="getHighlightedSegment('passage-title')">
                        </h2>
                    </div>

                    <div class="space-y-5 p-5 text-base leading-relaxed select-text" :style="contentZoom">

                        <!-- ── Paragraph A (no drop zone — example given) ── -->
                        <div class="px-1">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="inline-flex min-h-8 min-w-36 items-center justify-center rounded border-2 border-gray-200 bg-gray-50 px-3 py-1 text-xs text-gray-400 italic">
                                    Paragraph A — example
                                </span>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-a" v-html="getHighlightedSegment('para-a')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph B — Question 1 ── -->
                        <div class="px-1">
                            <div
                                id="question-1"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 1"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 min-w-36 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                    :class="dragOverQuestion === 1 ? 'border-blue-500 bg-blue-50' : answers.q1 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 1)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 1)"
                                    @click.stop="clearHeadingAnswer(1)"
                                    :title="answers.q1 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q1" class="text-xs leading-tight">{{ answers.q1 }}. {{ getHeadingLabel(answers.q1) }}</span>
                                    <span v-else class="font-bold text-gray-400">1</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 1)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(1) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 1 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-b" v-html="getHighlightedSegment('para-b')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph C (no drop zone) ── -->
                        <div class="px-1">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="inline-flex min-h-8 min-w-36 items-center justify-center rounded border-2 border-gray-200 bg-gray-50 px-3 py-1 text-xs text-gray-400 italic">

                                </span>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-c" v-html="getHighlightedSegment('para-c')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph D — Question 2 ── -->
                        <div class="px-1">
                            <div
                                id="question-2"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 2"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 min-w-36 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                    :class="dragOverQuestion === 2 ? 'border-blue-500 bg-blue-50' : answers.q2 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 2)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 2)"
                                    @click.stop="clearHeadingAnswer(2)"
                                    :title="answers.q2 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q2" class="text-xs leading-tight">{{ answers.q2 }}. {{ getHeadingLabel(answers.q2) }}</span>
                                    <span v-else class="font-bold text-gray-400">2</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 2)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(2) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 2 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-d" v-html="getHighlightedSegment('para-d')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph E — Question 3 ── -->
                        <div class="px-1">
                            <div
                                id="question-3"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 3"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 min-w-36 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                    :class="dragOverQuestion === 3 ? 'border-blue-500 bg-blue-50' : answers.q3 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 3)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 3)"
                                    @click.stop="clearHeadingAnswer(3)"
                                    :title="answers.q3 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q3" class="text-xs leading-tight">{{ answers.q3 }}. {{ getHeadingLabel(answers.q3) }}</span>
                                    <span v-else class="font-bold text-gray-400">3</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 3)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(3) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 3 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-e" v-html="getHighlightedSegment('para-e')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph F — Question 4 ── -->
                        <div class="px-1">
                            <div
                                id="question-4"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 4"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 min-w-36 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                    :class="dragOverQuestion === 4 ? 'border-blue-500 bg-blue-50' : answers.q4 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 4)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 4)"
                                    @click.stop="clearHeadingAnswer(4)"
                                    :title="answers.q4 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q4" class="text-xs leading-tight">{{ answers.q4 }}. {{ getHeadingLabel(answers.q4) }}</span>
                                    <span v-else class="font-bold text-gray-400">4</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 4)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(4) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 4 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-f" v-html="getHighlightedSegment('para-f')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph G — Question 5 ── -->
                        <div class="px-1">
                            <div
                                id="question-5"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 5"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 min-w-36 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                    :class="dragOverQuestion === 5 ? 'border-blue-500 bg-blue-50' : answers.q5 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 5)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 5)"
                                    @click.stop="clearHeadingAnswer(5)"
                                    :title="answers.q5 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q5" class="text-xs leading-tight">{{ answers.q5 }}. {{ getHeadingLabel(answers.q5) }}</span>
                                    <span v-else class="font-bold text-gray-400">5</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 5)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(5) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 5 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                            <p class="text-justify text-gray-700">
                                <span data-segment-id="para-g" v-html="getHighlightedSegment('para-g')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph H — Question 6 ── -->
                        <div class="px-1">
                            <div
                                id="question-6"
                                class="mb-2 flex items-center gap-2"
                                @mouseenter="hoveredQuestion = 6"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="inline-flex min-h-8 min-w-36 flex-1 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                    :class="dragOverQuestion === 6 ? 'border-blue-500 bg-blue-50' : answers.q6 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                    @dragover="handleHeadingDragOver($event, 6)"
                                    @dragleave="handleHeadingDragLeave"
                                    @drop="handleHeadingDrop($event, 6)"
                                    @click.stop="clearHeadingAnswer(6)"
                                    :title="answers.q6 ? 'Click to clear' : 'Drop heading here'"
                                >
                                    <span v-if="answers.q6" class="text-xs leading-tight">{{ answers.q6 }}. {{ getHeadingLabel(answers.q6) }}</span>
                                    <span v-else class="font-bold text-gray-400">6</span>
                                </span>
                                <button
                                    @click.stop="emit('toggleFlag', 6)"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(6) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 6 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                </button>
                            </div>
                            <p class="text-justify text-gray-700">

                                <span data-segment-id="para-h" v-html="getHighlightedSegment('para-h')"></span>
                            </p>
                        </div>

                        <!-- ── Paragraph I (no drop zone) ── -->
                        <div class="px-1">
                            <p class="text-justify text-gray-700">

                                <span data-segment-id="para-i" v-html="getHighlightedSegment('para-i')"></span>
                            </p>
                        </div>

                    </div><!-- /passage content -->
                </div><!-- /left panel -->

                <!-- ── Resize Handle ── -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center border border-gray-300 bg-white">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)"/>
                        </svg>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     RIGHT PANEL — All questions + draggable list
                ══════════════════════════════════════════════════════ -->
                <div
                    class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ width: `calc(${100 - leftPanelWidth}% - 1.25rem)` }" @mouseup="handleContentTextSelect"
                >
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4 select-text" :style="contentZoom">

                            <!-- ── Questions 1-6: Draggable headings list ── -->
                            <div class="p-4">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q1-6-title" v-html="getHighlightedSegment('q1-6-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span data-segment-id="q1-6-instruction1" v-html="getHighlightedSegment('q1-6-instruction1')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q1-6-instruction2" v-html="getHighlightedSegment('q1-6-instruction2')"></span>
                                    </p>
                                </div>

                                <!-- List of headings (static reference) -->


                                <!-- Draggable pool (only unused headings) -->
                                <div class="border border-gray-300 p-4">
                                    <h4 class="mb-2 text-sm font-semibold text-gray-500">Drag to the paragraph on the left:</h4>
                                    <div class="space-y-1.5 text-sm">
                                        <div
                                            v-for="option in availableHeadingOptions"
                                            :key="option.value"
                                            class="flex cursor-grab items-center gap-2 rounded border border-gray-300 bg-white px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                            :class="{ 'opacity-40': draggedHeading === option.value }"
                                            draggable="true"
                                            @dragstart="handleHeadingDragStart($event, option.value)"
                                            @dragend="handleHeadingDragEnd"
                                        >
                                            <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                            <span class="font-bold text-gray-800">{{ option.value }}.</span>
                                            <span class="text-gray-600">{{ option.label }}</span>
                                        </div>
                                        <p v-if="!availableHeadingOptions.length" class="text-xs text-gray-400 italic py-1">
                                            All headings have been placed.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 7-10: Multiple choice ── -->
                            <div class="p-4">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q7-10-title" v-html="getHighlightedSegment('q7-10-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q7-10-instruction" v-html="getHighlightedSegment('q7-10-instruction')"></span>
                                    </p>
                                </div>
                                <div class="space-y-3">

                                    <!-- Q7 -->
                                    <div id="question-7" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">7.</span>
                                                <span data-segment-id="q7-text" class="select-text" v-html="getHighlightedSegment('q7-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 7)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(7) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 7 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div class="space-y-1.5 text-sm">
                                            <label v-for="opt in ['a','b','c','d']" :key="opt" class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q7" type="radio" :value="opt.toUpperCase()" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500 shrink-0"/>
                                                <span :data-segment-id="`q7-${opt}`" class="select-text text-gray-700" v-html="getHighlightedSegment(`q7-${opt}`)"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q8 -->
                                    <div id="question-8" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">8.</span>
                                                <span data-segment-id="q8-text" class="select-text" v-html="getHighlightedSegment('q8-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 8)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(8) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 8 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div class="space-y-1.5 text-sm">
                                            <label v-for="opt in ['a','b','c','d']" :key="opt" class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q8" type="radio" :value="opt.toUpperCase()" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500 shrink-0"/>
                                                <span :data-segment-id="`q8-${opt}`" class="select-text text-gray-700" v-html="getHighlightedSegment(`q8-${opt}`)"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q9 -->
                                    <div id="question-9" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">9.</span>
                                                <span data-segment-id="q9-text" class="select-text" v-html="getHighlightedSegment('q9-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 9)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(9) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 9 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div class="space-y-1.5 text-sm">
                                            <label v-for="opt in ['a','b','c','d']" :key="opt" class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q9" type="radio" :value="opt.toUpperCase()" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500 shrink-0"/>
                                                <span :data-segment-id="`q9-${opt}`" class="select-text text-gray-700" v-html="getHighlightedSegment(`q9-${opt}`)"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Q10 -->
                                    <div id="question-10" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">10.</span>
                                                <span data-segment-id="q10-text" class="select-text" v-html="getHighlightedSegment('q10-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 10)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(10) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 10 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <div class="space-y-1.5 text-sm">
                                            <label v-for="opt in ['a','b','c','d']" :key="opt" class="flex items-center gap-2 cursor-pointer">
                                                <input v-model="answers.q10" type="radio" :value="opt.toUpperCase()" class="h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-500 shrink-0"/>
                                                <span :data-segment-id="`q10-${opt}`" class="select-text text-gray-700" v-html="getHighlightedSegment(`q10-${opt}`)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Questions 11-13: Short answer ── -->
                            <div class="p-4">
                                <div class="mb-4">
                                    <h3 class="text-base font-bold text-gray-900 mb-1">
                                        <span data-segment-id="q11-13-title" v-html="getHighlightedSegment('q11-13-title')"></span>
                                    </h3>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q11-13-instruction" v-html="getHighlightedSegment('q11-13-instruction')"></span>
                                    </p>
                                </div>
                                <div class="space-y-3">

                                    <!-- Q11 -->
                                    <div id="question-11" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">11.</span>
                                                <span data-segment-id="q11-text" class="select-text" v-html="getHighlightedSegment('q11-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 11)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(11) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 11 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <input v-model="answers.q11" type="text" placeholder="11"
                                            class="w-full border border-gray-300 px-3 py-1.5 text-sm focus:border-black focus:outline-none"/>
                                    </div>

                                    <!-- Q12 -->
                                    <div id="question-12" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">12.</span>
                                                <span data-segment-id="q12-text" class="select-text" v-html="getHighlightedSegment('q12-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 12)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(12) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 12 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <input v-model="answers.q12" type="text" placeholder="12"
                                            class="w-full border border-gray-300 px-3 py-1.5 text-sm focus:border-black focus:outline-none"/>
                                    </div>

                                    <!-- Q13 -->
                                    <div id="question-13" class="border border-gray-300 p-3"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start justify-between mb-2">
                                            <p class="text-sm text-gray-700 flex-1">
                                                <span class="font-bold mr-1">13.</span>
                                                <span data-segment-id="q13-text" class="select-text" v-html="getHighlightedSegment('q13-text')"></span>
                                            </p>
                                            <button @click.stop="emit('toggleFlag', 13)"
                                                class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(13) ? 'border-red-400 text-red-500 opacity-100' : hoveredQuestion === 13 ? 'border-gray-400 text-gray-500 opacity-100' : 'border-gray-300 text-gray-400 opacity-30 hover:opacity-100'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                            </button>
                                        </div>
                                        <input v-model="answers.q13" type="text" placeholder="13"
                                            class="w-full border border-gray-300 px-3 py-1.5 text-sm focus:border-black focus:outline-none"/>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div><!-- /right panel -->

            </div>
        </div>

        <!-- ── Context Menu ── -->
        <Teleport to="body">
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop>
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"/>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                        </button>
                        <button @click="applyHighlight('yellow')"
                            class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                        </button>
                    </div>
                    <div class="tooltip-arrow"></div>
                </div>
            </div>
        </Teleport>

        <!-- ── Delete Highlight Tooltip ── -->
        <Teleport to="body">
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                    <div class="tooltip-arrow-up"></div>
                    <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                        <button @click="confirmDeleteHighlight" type="button"
                            class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                            <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6m5 0V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span class="mt-1 text-xs font-medium text-gray-700">Delete Highlight</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Note Hover Tooltip ── -->
        <Teleport to="body">
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave" @click.stop>
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
        </Teleport>

        <!-- ── Note Input Modal ── -->
        <Teleport to="body">
            <div v-if="showNoteInput"
                class="fixed z-[9999] w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-xl"
                :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop>
                <div class="mb-3">
                    <p class="mb-3 rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                    <input v-model="noteInputText" type="text" placeholder="Write your note here..."
                        class="note-input-field w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                        @keyup.enter="saveNote" @keyup.escape="cancelNote"/>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cancelNote" class="rounded border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</button>
                    <button @click="saveNote" class="rounded bg-gray-900 px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
