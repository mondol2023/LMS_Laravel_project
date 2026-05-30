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

// Define emits
const emit = defineEmits<{
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

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const passageTextRef = ref<HTMLDivElement | null>(null);
const questionsTextRef = ref<HTMLDivElement | null>(null);
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
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

// Passage text
const passageText =
    ref(`<span class="font-bold">Koalas</span>

<strong>A</strong> Koalas are just too nice for their own good. And except for the occasional baby taken by birds of prey, koalas have no natural enemies. In an ideal world, the life of an arboreal couch potato would be perfectly safe and acceptable.

<strong>B</strong> Just two hundred years ago, koalas flourished across Australia. Now they seem to be in decline, but exact numbers are not available as the species would not seem to be 'under threat'. Their problem, however, has been man, more specifically, the white man. Koala and aborigine had co-existed peacefully for centuries.

<strong>C</strong> Today koalas are found only in scattered pockets of southeast Australia, where they seem to be at risk on several fronts. The koala's only food source, the eucalyptus tree has declined. In the past 200 years, a third of Australia's eucalyptus forests have disappeared. Koalas have been killed by parasites, chlamydia epidemics and a tumour-causing retro-virus. And every year 11000 are killed by cars, ironically most of them in wildlife sanctuaries, and thousands are killed by poachers. Some are also taken illegally as pets. The animals usually soon die, but they are easily replaced.

<strong>D</strong> Bush fires pose another threat. The horrific ones that raged in New South Wales recently killed between 100 and 1000 koalas. Many that were taken into sanctuaries and shelters were found to have burnt their paws on the glowing embers. But zoologists say that the species should recover. The koalas will be aided by the eucalyptus, which grows quickly and is already burgeoning forth after the fires. So the main problem to their survival is their slow reproductive rate – they produce only one baby a year over a reproductive lifespan of about nine years.

<strong>E</strong> The latest problem for the species is perhaps more insidious. With plush, grey fur, dark amber eyes and button nose, koalas are cuddliness incarnate. Australian zoos and wildlife parks have taken advantage of their uncomplaining attitudes, and charge visitors to be photographed hugging the furry bundles. But people may not realise how cruel this is, but because of the koala's delicate disposition, constant handling can push an already precariously balanced physiology over the edge.

<strong>F</strong> Koalas only eat the foliage of certain species of eucalyptus trees, between 600 and 1250 grams a day. The tough leaves are packed with cellulose, tannins, aromatic oils and precursors of toxic cyanides. To handle this cocktail, koalas have a specialised digestive system. Cellulose-digesting bacteria in the break down fibre, while a specially adapted gut and liver process the toxins. To digest their food properly, koalas must sit still for 21 hours every day.

<strong>G</strong> Koalas are the epitome of innocence and inoffensiveness. Although they are capable of ripping open a man's arm with their needle-sharp claws, or giving a nasty nip, they simply wouldn't. If you upset a koala, it may blink or swallow, or hiccup. But attack? No way! Koalas are just not aggressive. They use their claws to grip the hard smooth bark of eucalyptus trees.

<strong>H</strong> They are also very sensitive, and the slightest upset can prevent them from breeding, cause them to go off their food, and succumb to gut infections. Koalas are stoic creatures and put on a brave face until they are at death's door. One day they may appear healthy, the next they could be dead. Captive koalas have to be weighed daily to check that they are feeding properly. A sudden loss of weight is usually the only warning keepers have that their charge is ill. Only two keepers plus a vet were allowed to handle London Zoo's koalas, as these creatures are only comfortable with people they know. A request for the koala to be taken to meet the Queen was refused because of the distress this would have caused the marsupial. Sadly, London's Zoo no longer has a koala. Two years ago the female koala died of a cancer caused by a retrovirus. When they come into heat, female koalas become more active, and start losing weight, but after about sixteen days, heat ends and the weight piles back on. London's koala did not. Surgery revealed hundreds of pea-sized tumours. Almost every zoo in Australia has koalas – the marsupial has become the Animal Ambassador of the nation, but nowhere outside Australia would handling by the public be allowed. Koala cuddling screams in the face of every rule of good care. First, some zoos allow koalas to be passed from stranger to stranger, many children who love to squeeze. Secondly, most people have no idea of how to handle the animals; they like to cling on to their handler, all in their own good time and use his or her arm as a tree. For such reasons, the Association of Fauna and Marine Parks, an Australian conservation society, is campaigning to ban koala cuddling. Policy on koala handling is determined by state government authorities, and the Australian Nature Conservation Agency aims to institute national guidelines. Following a wave of publicity, some zoos and wildlife parks have stopped turning their koalas into photo props.`);

// Text segments with their cumulative offsets in the full text
const textSegments = ref([
    { id: 'part-header', text: 'Part 1', offset: -100 },
    { id: 'part-instructions', text: 'You should spend about 20 minutes on Questions 1–13, which are based on Reading Passage 1 below.', offset: -93 },
    { id: 'passage-title', text: 'Koalas', offset: 0 },
    {
        id: 'passage',
        text: passageText.value,
        offset: 0,
    },
    // Questions 1-5
    { id: 'q1-5-title', text: 'Questions 1-5', offset: passageText.value.length + 1 },
    { id: 'q1-5-inst', text: 'Choose the correct letter, A, B, C or D.', offset: passageText.value.length + 15 },
    { id: 'q1-5-inst2', text: 'Write the correct letter in boxes 1-5 on your answer sheet.', offset: passageText.value.length + 56 },
    // Q1
    { id: 'q1-num', text: '1.', offset: passageText.value.length + 120 },
    { id: 'q1', text: 'The main reason why koala declined is that they are killed EXCEPT FOR', offset: passageText.value.length + 123 },
    { id: 'q1-A', text: 'by poachers', offset: passageText.value.length + 193 },
    { id: 'q1-B', text: 'by diseases they got', offset: passageText.value.length + 207 },
    { id: 'q1-C', text: 'giving too many birth yet survived little!', offset: passageText.value.length + 230 },
    { id: 'q1-D', text: 'accidents on the road', offset: passageText.value.length + 274 },
    // Q2
    { id: 'q2-num', text: '2.', offset: passageText.value.length + 298 },
    { id: 'q2', text: 'What can help koalas fully digest their food?', offset: passageText.value.length + 301 },
    { id: 'q2-A', text: 'toxic substance in the leaves', offset: passageText.value.length + 347 },
    { id: 'q2-B', text: 'organs that dissolve the fibres', offset: passageText.value.length + 379 },
    { id: 'q2-C', text: 'remaining inactive for a period to digest', offset: passageText.value.length + 413 },
    { id: 'q2-D', text: 'eating eucalyptus trees', offset: passageText.value.length + 457 },
    // Q3
    { id: 'q3-num', text: '3.', offset: passageText.value.length + 483 },
    { id: 'q3', text: 'What would koalas do when facing the dangerous situation?', offset: passageText.value.length + 486 },
    { id: 'q3-A', text: 'show signs of being offended', offset: passageText.value.length + 544 },
    { id: 'q3-B', text: 'counter attack furiously', offset: passageText.value.length + 575 },
    { id: 'q3-C', text: 'use sharp claws to rip the man', offset: passageText.value.length + 602 },
    { id: 'q3-D', text: 'use claws to grip the bark of trees.', offset: passageText.value.length + 635 },
    // Q4
    { id: 'q4-num', text: '4.', offset: passageText.value.length + 674 },
    { id: 'q4', text: 'In what ways Australian zoos exploit koalas?', offset: passageText.value.length + 677 },
    { id: 'q4-A', text: 'encourage people to breed koalas as pets', offset: passageText.value.length + 722 },
    { id: 'q4-B', text: 'allow tourists to hug the koalas', offset: passageText.value.length + 765 },
    { id: 'q4-C', text: 'put them on the trees as a symbol', offset: passageText.value.length + 800 },
    { id: 'q4-D', text: 'establish a koala campaign', offset: passageText.value.length + 836 },
    // Q5
    { id: 'q5-num', text: '5.', offset: passageText.value.length + 864 },
    { id: 'q5', text: 'What would the government do to protect koalas from being endangered?', offset: passageText.value.length + 867 },
    { id: 'q5-A', text: 'introduce koala protection guidelines', offset: passageText.value.length + 937 },
    { id: 'q5-B', text: 'close some of the zoos', offset: passageText.value.length + 977 },
    { id: 'q5-C', text: 'encourage people to resist visiting the zoos', offset: passageText.value.length + 1002 },
    { id: 'q5-D', text: 'persuade the public to learn more knowledge', offset: passageText.value.length + 1049 },
    // Questions 6-12
    { id: 'q6-12-title', text: 'Questions 6–12', offset: passageText.value.length + 1095 },
    { id: 'q6-12-inst', text: 'Do the following statements agree with the information given in Reading Passage 1 ?', offset: passageText.value.length + 1110 },
    { id: 'q6-12-inst2', text: 'In boxes 6–12 on your answer sheet, write:', offset: passageText.value.length + 1193 },
    { id: 'q6-12-yes', text: 'YES', offset: passageText.value.length + 1236 },
    { id: 'q6-12-yes-desc', text: 'if the statement agrees with the information', offset: passageText.value.length + 1240 },
    { id: 'q6-12-no', text: 'NO', offset: passageText.value.length + 1285 },
    { id: 'q6-12-no-desc', text: 'if the statement contradicts the information', offset: passageText.value.length + 1288 },
    { id: 'q6-12-ng', text: 'NOT GIVEN', offset: passageText.value.length + 1333 },
    { id: 'q6-12-ng-desc', text: 'if there is no information on this passage', offset: passageText.value.length + 1343 },
    // Q6-12 question texts
    { id: 'q6-num', text: '6.', offset: passageText.value.length + 1390 },
    { id: 'q6', text: 'Newcoming human settlers caused danger to koalas.', offset: passageText.value.length + 1393 },
    { id: 'q7-num', text: '7.', offset: passageText.value.length + 1443 },
    { id: 'q7', text: 'Koalas can still be seen in most of the places in Australia.', offset: passageText.value.length + 1446 },
    { id: 'q8-num', text: '8.', offset: passageText.value.length + 1507 },
    { id: 'q8', text: 'It takes decades for the eucalyptus trees to recover after the fire.', offset: passageText.value.length + 1510 },
    { id: 'q9-num', text: '9.', offset: passageText.value.length + 1579 },
    { id: 'q9', text: 'Koalas will fight each other when food becomes scarce.', offset: passageText.value.length + 1582 },
    { id: 'q10-num', text: '10.', offset: passageText.value.length + 1637 },
    { id: 'q10', text: 'It is not easy to notice that koalas are ill.', offset: passageText.value.length + 1641 },
    { id: 'q11-num', text: '11.', offset: passageText.value.length + 1687 },
    { id: 'q11', text: 'Koalas are easily infected with human contagious disease via cuddling.', offset: passageText.value.length + 1691 },
    { id: 'q12-num', text: '12.', offset: passageText.value.length + 1762 },
    { id: 'q12', text: 'Koalas like to hold a person\'s arm when they are embraced.', offset: passageText.value.length + 1766 },
    // Question 13
    { id: 'q13-title', text: 'Question 13', offset: passageText.value.length + 1825 },
    { id: 'q13-inst', text: 'Choose the correct letter A, B, C or D.', offset: passageText.value.length + 1837 },
    { id: 'q13-inst2', text: 'Write the correct letter in box 13 on your answer sheet.', offset: passageText.value.length + 1877 },
    { id: 'q13-num', text: '13.', offset: passageText.value.length + 1934 },
    { id: 'q13', text: 'From your opinion, this article is written by:', offset: passageText.value.length + 1938 },
    { id: 'q13-A', text: 'a journalist who writes for a magazine', offset: passageText.value.length + 1985 },
    { id: 'q13-B', text: 'a zookeeper in London Zoo', offset: passageText.value.length + 2026 },
    { id: 'q13-C', text: 'a tourist travelling back from Australia', offset: passageText.value.length + 2054 },
    { id: 'q13-D', text: 'a government official who studies koalas to establish a law', offset: passageText.value.length + 2096 },
]);

// Convert plain text offset to HTML offset
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

// Helper to get a highlighted version of a specific text segment
const getHighlightedSegment = (segmentText: string) => {
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

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

const handlePassageTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && (passageTextRef.value || questionsTextRef.value)) {
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

            if (selection && range) {
                let targetSpan = range.startContainer;

                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE) {
                    targetSpan = targetSpan.parentNode;
                }

                while (
                    targetSpan &&
                    !(targetSpan as Element).classList?.contains('text-gray-700') &&
                    !(targetSpan as Element).classList?.contains('text-gray-800') &&
                    !(targetSpan as Element).classList?.contains('text-gray-900') &&
                    !(targetSpan as Element).classList?.contains('select-text') &&
                    !(targetSpan as Element).classList?.contains('text-lg')
                ) {
                    const parent = targetSpan.parentNode;
                    if (!parent) break;
                    targetSpan = parent;
                }

                if (targetSpan) {
                    const spanText = (targetSpan as Element).textContent || '';
                    const isPassageText = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500;

                    let segment = null;

                    if (isPassageText) {
                        segment = textSegments.value[3];
                    } else {
                        segment = textSegments.value.find((s) => s.text === spanText.trim());

                        if (!segment) {
                            segment = textSegments.value.find((s) => {
                                const normalizedSpan = spanText.trim().replace(/\s+/g, ' ');
                                const normalizedSegment = s.text.trim().replace(/\s+/g, ' ');
                                return (
                                    normalizedSpan === normalizedSegment ||
                                    normalizedSpan.includes(normalizedSegment) ||
                                    normalizedSegment.includes(normalizedSpan)
                                );
                            });
                        }
                    }

                    if (segment) {
                        const normalizedSelected = selected.trim();

                        const preSelectionRange = document.createRange();
                        preSelectionRange.selectNodeContents(targetSpan as Element);
                        preSelectionRange.setEnd(range.startContainer, range.startOffset);

                        const plainTextOffset = preSelectionRange.toString().length;

                        const startOffset = segment.offset + plainTextOffset;
                        const endOffset = startOffset + normalizedSelected.length;

                        selectedText.value = normalizedSelected;
                        selectionRange.value = { start: startOffset, end: endOffset };
                    }
                }
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

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
        const input = document.querySelector<HTMLTextAreaElement>('.note-input-field');
        input?.focus();
    }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        showContextMenu.value = false;
        closeDeleteTooltip();
    }
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

const handleTooltipMouseLeave = () => {
    showNoteTooltip.value = false;
    hoveredNoteId.value = null;
    hoveredNoteText.value = '';
};

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

watch(
    notes,
    (newNotes) => {
        emit('notesChange', newNotes);
    },
    { deep: true },
);

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
    <div class="pb-20 sm:pb-24 md:pb-32">
        <!-- Part 1 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2" @mouseup="handlePassageTextSelect"
            @click="handleContentClick">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="'Part 1'"></p>
            <p class="text-sm text-gray-700 select-text"
                v-html="'You should spend about 20 minutes on Questions 1–13, which are based on Reading Passage 1 below.'">
            </p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row"
                :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto pb-8 lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="space-y-2" :style="contentZoom">
                        <div ref="passageTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick"
                            class="space-y-6 leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span class="text-lg select-text" data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[3].text)"></span>
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
                    <div ref="questionsTextRef" @mouseup="handlePassageTextSelect" @click="handleContentClick"
                        class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 1-5 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text"
                                                v-html="getHighlightedSegment('Questions 1-5')"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text"
                                            v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D.')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text"
                                            v-html="getHighlightedSegment('Write the correct letter in boxes 1-5 on your answer sheet.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 1 -->
                                    <div id="question-1" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 1"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 1 || isQuestionFlagged(1)"
                                            @click.stop="toggleFlag(1)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(1) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('1.')"></span>
                                            <span class="text-base text-gray-900 select-text"
                                                v-html="getHighlightedSegment('The main reason why koala declined is that they are killed EXCEPT FOR')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q1" value="A"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('by poachers')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q1" value="B"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('by diseases they got')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q1" value="C"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('giving too many birth yet survived little!')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q1" value="D"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('accidents on the road')"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 2 -->
                                    <div id="question-2" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 2"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 2 || isQuestionFlagged(2)"
                                            @click.stop="toggleFlag(2)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(2) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('2.')"></span>
                                            <span class="text-base text-gray-900 select-text"
                                                v-html="getHighlightedSegment('What can help koalas fully digest their food?')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q2" value="A"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('toxic substance in the leaves')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q2" value="B"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('organs that dissolve the fibres')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q2" value="C"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('remaining inactive for a period to digest')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q2" value="D"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('eating eucalyptus trees')"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 3 -->
                                    <div id="question-3" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 3"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 3 || isQuestionFlagged(3)"
                                            @click.stop="toggleFlag(3)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(3) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('3.')"></span>
                                            <span class="text-base text-gray-900 select-text"
                                                v-html="getHighlightedSegment('What would koalas do when facing the dangerous situation?')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q3" value="A"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('show signs of being offended')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q3" value="B"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('counter attack furiously')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q3" value="C"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('use sharp claws to rip the man')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q3" value="D"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('use claws to grip the bark of trees.')"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 4 -->
                                    <div id="question-4" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 4"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 4 || isQuestionFlagged(4)"
                                            @click.stop="toggleFlag(4)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(4) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('4.')"></span>
                                            <span class="text-base text-gray-900 select-text"
                                                v-html="getHighlightedSegment('In what ways Australian zoos exploit koalas?')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q4" value="A"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('encourage people to breed koalas as pets')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q4" value="B"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('allow tourists to hug the koalas')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q4" value="C"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('put them on the trees as a symbol')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q4" value="D"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('establish a koala campaign')"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Question 5 -->
                                    <div id="question-5" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 5"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 5 || isQuestionFlagged(5)"
                                            @click.stop="toggleFlag(5)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(5) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('5.')"></span>
                                            <span class="text-base text-gray-900 select-text"
                                                v-html="getHighlightedSegment('What would the government do to protect koalas from being endangered?')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q5" value="A"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('introduce koala protection guidelines')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q5" value="B"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('close some of the zoos')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q5" value="C"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('encourage people to resist visiting the zoos')"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q5" value="D"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900 select-text"
                                                    v-html="getHighlightedSegment('persuade the public to learn more knowledge')"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 6-12 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text"
                                                v-html="getHighlightedSegment('Questions 6–12')"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text"
                                            v-html="getHighlightedSegment('Do the following statements agree with the information given in Reading Passage 1 ?')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-2">
                                        <span class="select-text"
                                            v-html="getHighlightedSegment('In boxes 6–12 on your answer sheet, write:')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text font-bold text-gray-900"
                                            v-html="getHighlightedSegment('YES')"></span>
                                        <span class="select-text mx-1"
                                            v-html="getHighlightedSegment('if the statement agrees with the information')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text font-bold text-gray-900"
                                            v-html="getHighlightedSegment('NO')"></span>
                                        <span class="select-text mx-1"
                                            v-html="getHighlightedSegment('if the statement contradicts the information')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text font-bold text-gray-900"
                                            v-html="getHighlightedSegment('NOT GIVEN')"></span>
                                        <span class="select-text mx-1"
                                            v-html="getHighlightedSegment('if there is no information on this passage')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 6 -->
                                    <div id="question-6" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 6"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('6.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('Newcoming human settlers caused danger to koalas.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q6" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q6" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q6" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 6 || isQuestionFlagged(6)"
                                                @click.stop="toggleFlag(6)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(6) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 7 -->
                                    <div id="question-7" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('7.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('Koalas can still be seen in most of the places in Australia.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q7" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q7" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q7" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                                @click.stop="toggleFlag(7)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 8 -->
                                    <div id="question-8" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('8.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('It takes decades for the eucalyptus trees to recover after the fire.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q8" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q8" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q8" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                @click.stop="toggleFlag(8)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 9 -->
                                    <div id="question-9" class="relative bg-white p-2" @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('9.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('Koalas will fight each other when food becomes scarce.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                @click.stop="toggleFlag(9)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 10 -->
                                    <div id="question-10" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('10.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('It is not easy to notice that koalas are ill.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                                @click.stop="toggleFlag(10)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 11 -->
                                    <div id="question-11" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('11.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('Koalas are easily infected with human contagious disease via cuddling.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q11" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                @click.stop="toggleFlag(11)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Question 12 -->
                                    <div id="question-12" class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <div class="mb-2 flex items-start gap-2 pr-8">
                                            <span class="text-base font-bold text-gray-900 select-text"
                                                v-html="getHighlightedSegment('12.')"></span>
                                            <span class="text-base text-gray-900 select-text flex-1"
                                                v-html="getHighlightedSegment('Koalas like to hold a person\'s arm when they are embraced.')"></span>
                                        </div>
                                        <div class="ml-6 flex flex-wrap items-center gap-4 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="YES"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">YES</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="NO"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NO</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q12" value="NOT GIVEN"
                                                    class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <div class="absolute top-2 right-2 w-7 h-7">
                                            <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                @click.stop="toggleFlag(12)"
                                                class="absolute inset-0 flex items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 13 -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="select-text"
                                                v-html="getHighlightedSegment('Question 13')"></span>
                                        </h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-700 mb-1">
                                        <span class="select-text"
                                            v-html="getHighlightedSegment('Choose the correct letter A, B, C or D.')"></span>
                                    </p>
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        <span class="select-text"
                                            v-html="getHighlightedSegment('Write the correct letter in box 13 on your answer sheet.')"></span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <div id="question-13" class="relative p-2" @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null">
                                        <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <div class="text-base">
                                            <p class="mb-3 text-gray-700">
                                                <span class="font-bold text-gray-900 select-text mx-1"
                                                    v-html="getHighlightedSegment('13.')"></span>
                                                <span class="select-text"
                                                    v-html="getHighlightedSegment('From your opinion, this article is written by:')"></span>
                                            </p>
                                            <div class="space-y-2 ml-6">
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q13" type="radio" value="A"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text"
                                                        v-html="getHighlightedSegment('a journalist who writes for a magazine')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q13" type="radio" value="B"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text"
                                                        v-html="getHighlightedSegment('a zookeeper in London Zoo')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q13" type="radio" value="C"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text"
                                                        v-html="getHighlightedSegment('a tourist travelling back from Australia')"></span>
                                                </label>
                                                <label class="flex items-center space-x-3">
                                                    <input v-model="answers.q13" type="radio" value="D"
                                                        class="h-4 w-4 border-gray-900 text-gray-900 focus:ring-gray-500" />
                                                    <span class="select-text"
                                                        v-html="getHighlightedSegment('a government official who studies koalas to establish a law')"></span>
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
    </div>

    <!-- Context Menu for Highlighting - Speech Bubble Style -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div class="highlight-tooltip pointer-events-auto fixed z-9999" :style="{
                left: `${contextMenuPosition.x}px`,
                top: `${contextMenuPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @click.stop>
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
            <div class="delete-highlight-tooltip fixed z-9999" :style="{
                left: `${deleteTooltipPosition.x}px`,
                top: `${deleteTooltipPosition.y}px`,
                transform: 'translateX(-50%)',
            }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md"
                    @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <div class="note-hover-tooltip pointer-events-auto fixed z-9999" :style="{
                left: `${noteTooltipPosition.x}px`,
                top: `${noteTooltipPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @mouseleave="handleTooltipMouseLeave" @click.stop>
                <div
                    class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="note-tooltip-icon shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </span>
                    <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText
                        }}</span>
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
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl" :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }" @click.stop>
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
                    class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote"
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
            </div>
        </div>
    </Teleport>
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

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
    color: inherit;
    -webkit-background-clip: padding-box !important;
    background-clip: padding-box !important;
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
</style>

<!-- Non-scoped styles for v-html generated note indicators -->
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
