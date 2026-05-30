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

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Resize functionality
const PANEL_WIDTH_KEY = 'reading-part1-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);
const passagePanelRef = ref<HTMLDivElement | null>(null);
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

// ─── Drag-and-drop state for Q1-6 ───────────────────────────────────────────
// People options
const peopleOptions = ref([
    { id: 'A', label: 'A', name: 'Stephanie Jenouvrier' },
    { id: 'B', label: 'B', name: 'Gerald Kooyman' },
    { id: 'C', label: 'C', name: 'Phil Trathan' },
    { id: 'D', label: 'D', name: 'David Ainley' },
    { id: 'E', label: 'E', name: 'John Turner' },
]);

// Answers for Q1-6 (drag-drop)
const dragAnswers = ref<{ [key: string]: string }>({
    q1: '',
    q2: '',
    q3: '',
    q4: '',
    q5: '',
    q6: '',
});

// Currently dragging item
const draggingItem = ref<string | null>(null);
const dragSource = ref<string | null>(null); // 'bank' or 'q1'..'q6'

const onDragStart = (event: DragEvent, personId: string, source: string) => {
    draggingItem.value = personId;
    dragSource.value = source;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', personId);
    }
};

const onDropToSlot = (event: DragEvent, questionKey: string) => {
    event.preventDefault();
    const personId = event.dataTransfer?.getData('text/plain') || draggingItem.value;
    if (!personId) return;

    // Always just place the person in the target slot
    // If dragging from another slot, clear that slot
    if (dragSource.value && dragSource.value !== 'bank' && dragSource.value !== questionKey) {
        dragAnswers.value[dragSource.value] = '';
    }

    dragAnswers.value[questionKey] = personId;
    draggingItem.value = null;
    dragSource.value = null;
};

const onDropToBank = (event: DragEvent) => {
    event.preventDefault();
    const personId = event.dataTransfer?.getData('text/plain') || draggingItem.value;
    if (!personId) return;

    // Clear the slot it came from
    if (dragSource.value && dragSource.value !== 'bank') {
        dragAnswers.value[dragSource.value] = '';
    }

    draggingItem.value = null;
    dragSource.value = null;
};

const onDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
};

// People are always available - can be used more than once
const usedPersonIds = computed(() => new Set<string>());

// Remove a person from a slot (click X)
const removeFromSlot = (questionKey: string) => {
    dragAnswers.value[questionKey] = '';
};

// Get person object by id
const getPersonById = (id: string) => peopleOptions.value.find((p) => p.id === id);

// ─── Other answers ─────────────────────────────────────────────────────────
const answers = ref({
    q7: '',
    q8: '',
    q9: '',
    q10: '',
    q11: '',
    q12: '',
    q13: '',
});

// ─── Passage text ──────────────────────────────────────────────────────────
const passageText = `
<b>A</b><br/> The emperor penguin is an impossible bird. It breeds in the middle of winter in some of the coldest places on Earth, surviving temperatures as low as −50°C and hurricane-force winds. In March or April, just as the Antarctic winter begins, the birds waddle across the sea ice to their colonies, where they mate. After the egg is laid, the females head back to sea to feed, leaving the males behind to incubate it. By the time the females return in July or August, when the eggs hatch, the males will have spent almost four months huddling together in the bitter cold without eating, losing half of their body weight. This extraordinary lifestyle has made the emperors famous. They have even been held up as role models by evangelical Christians. But these breathtaking birds will soon have to face the one thing they haven't evolved to cope with: warmth. Fast-forward a few decades, and many colonies will be on the road to extinction. Are we witnessing the last march of the emperor penguins?<br/><br/>

<b>B</b><br/> Finding out what's going on with emperor penguins is a huge challenge as almost all of their colonies are exceedingly difficult to get to. In fact, it was only this year that the first global census of the birds was published, based on an automated analysis of satellite images by the British Antarctic Survey. This revealed four previously unknown colonies, bringing the total to 46 (see map), and put the number of adults at 600,000, nearly double earlier estimates. That might sound like good news, but it's impossible to say whether the overall number of birds is rising or falling. "It's simply that we now have a better method to find them—remote sensing," says team member Phil Trathan.<br/><br/>

<b>C</b><br/> By far the most comprehensive insight into the highs and lows of emperor populations comes from just one colony, which happens to be next to the Dumont d'Urville research station on the Adélie coast of Antarctica. "After a snowstorm, they can see how many eggs have got frozen, and how many chicks have died," says biologist Stéphanie Jenouvrier of the Woods Hole Oceanographic Institution in Massachusetts, who studies the birds. This relatively small colony of 2,500 birds featured in the 2005 blockbuster documentary March of the Penguins.<br/><br/>

<b>D</b><br/> The Dumont d'Urville emperors have been closely monitored since 1962. During the 1970s and early 80s, the average winter temperature was −14.8°C, compared with a more typical −17.3°C. This "warm spell" reduced the extent of winter sea ice by around 11 percent and the penguin population by half. "When sea ice decreased, it caused strong mortality of emperor penguins," says Jenouvrier. Why are emperors so sensitive to changes in sea ice? Well, to start with, most never set foot on land. They aren't agile enough to scale the steep rocks and ice precipices that guard most of Antarctica's shoreline. All but two of the 46 colonies are on fast ice—sea ice stuck fast to the shore. So if the sea ice forms late or breaks up early, it won't last for the eight months or so these large birds need to breed and raise chicks.<br/><br/>

<b>E</b><br/> "Early break-up of sea ice can cause catastrophic breeding failure," says Trathan. Emperors live around 20 years, so colonies can survive a few bad breeding seasons, but persistent changes can be disastrous. What's more, emperors moult every year in January or February. The birds would freeze to death if they tried to swim during the 30 or so days it takes to grow new feathers, so they must find ice floes to shelter on that are large enough to survive this period. This may be an even more demanding period in the emperors' lives than the winter, because they have little time to fatten themselves up beforehand. "The adults are reliant on stable sea ice for moulting, and for me, that's the greatest concern," says Gerald Kooyman of Scripps Institution of Oceanography, one of the world's leading emperor penguin biologists. "They don't have any options. They have to moult."<br/><br/>

<b>F</b><br/> Last, but not least, the source of much of the penguins' energy, directly or indirectly, is krill—and krill also depend on sea ice. Young krill shelter and feed under it. "The sea ice is the basis of the Antarctic ecosystem," says Jenouvrier. For now, there is still plenty of sea ice. In fact, the extent of Antarctic sea ice in winter has increased slightly over the last 30 years. This has been caused by stronger winds blowing sea ice further away from the land, with more ice forming in the open water exposed by this movement. The stronger winds are thought to be a consequence of ozone loss, rather than global warming.<br/><br/>

<b>G</b><br/> But unlike the Arctic Ocean, where thick sea ice used to survive from year to year, in Antarctica almost all the sea ice melts every year. That means the extent of winter sea ice changes rapidly in response to any change in conditions. This can be seen around the rapidly warming Antarctic Peninsula, where winter sea ice extent is falling 1 or 2 percent each year. Here one small emperor colony, on the Dion Islands, has already died out. When it was discovered in 1948 it was home to 300 adults. By 1999, just 40 remained, and 10 years later they were all gone. Though no one knows for sure what caused the colony's demise, it coincided with a decline in the duration of winter sea ice. On the peninsula, populations of the other Antarctic native penguins, the Adélie and chinstrap, are also plummeting, probably because of the changing environment and declining krill. Matters haven't been helped by an invasion of non-native gentoo penguins, and other species like the king and macaroni penguins could follow.<br/><br/>

<b>H</b><br/> What's happening on the peninsula today could be happening all around Antarctica in the decades to come. "With a doubling of greenhouse gas concentrations over the next century, we estimate that the extent of Antarctic sea ice would decrease by about one third," says John Turner, a climatologist with the British Antarctic Survey. Earlier this year the emperor penguin was added to the IUCN's Red List for species threatened with extinction in the near future—"near" meaning in a century or two. When Jenouvrier's team used the observations at Dumont d'Urville to predict what will happen as the continent warms, they concluded that the colony is likely to decline by 81 percent by 2100 and be heading towards extinction.<br/><br/>

<b>I</b><br/> That is in line with a 2010 study by a team including Jenouvrier and David Ainley of the California-based ecological consultants H. T. Harvey and Associates. It predicted that all emperor colonies north of 70 degrees latitude—about 35 percent of the total population—would decline or disappear if the world warms by 2°C, although a few colonies south of 73 degrees might grow a little. This might not sound too bad, but both these studies are based on what increasingly appear to be overly optimistic assumptions. If we continue as we are, the global temperature will climb above 2°C before 2050, on course to a 5 or 6°C rise by 2100. "If the Earth warms by 5 or 6 degrees, I can't see that there's going to be much sea ice left anywhere on Earth," says Ainley. And if the sea ice vanishes, the emperor penguins will vanish too.
`;

// Text segments for highlight support
// Use negative offsets for headers before passage, passage at 0, questions start at 10000 (well after passage)
const textSegments = ref([
    // Part 1 header text segments (negative offsets to come before passage)
    { id: 'part1-title', text: 'Part 1', offset: -100 },
    { id: 'part1-desc', text: 'Read the text and answer questions 1–13.', offset: -93 },
    { id: 'part1-passage-title', text: 'The Last March of the Emperor Penguins', offset: -51 },
    // Single passage text segment (plain text length can be up to ~6000 chars)
    { id: 'passage', text: passageText, offset: 0 },
    // Q1-6 segments (offsets start at 10000 to ensure no overlap with passage)
    { id: 'q1-6-title', text: 'Questions 1-6', offset: 10000 },
    { id: 'q1-6-inst1', text: 'Use the information in the passage to match the people (listed A-E) with opinions or deeds below.', offset: 10014 },
    { id: 'q1-6-inst2', text: 'Write the appropriate letters A-E in boxes 1-6 on your answer sheet.', offset: 10112 },
    { id: 'q1-6-inst3', text: 'NB You may use any letter more than once.', offset: 10182 },
    { id: 'q1-text', text: 'Penguin breeding is threatened by sea ice melting in advance.', offset: 10230 },
    { id: 'q2-text', text: 'About 30% sea ice would disappear in the future.', offset: 10292 },
    { id: 'q3-text', text: 'Penguin needs constant sea ice for feather changing.', offset: 10341 },
    { id: 'q4-text', text: 'Dead chicks are easy to be counted after a storm.', offset: 10394 },
    { id: 'q5-text', text: 'No sea ice left in case global temperature increased certain degrees.', offset: 10444 },
    { id: 'q6-text', text: 'Sea ice provides foundation for Antarctic ecology.', offset: 10514 },
    // Q7-10 segments
    { id: 'q7-10-title', text: 'Questions 7-10', offset: 10570 },
    { id: 'q7-10-inst-choose', text: 'Do the following statements agree with the information given in Reading Passage 1?', offset: 10585 },
    { id: 'q7-10-inst-true', text: 'TRUE', offset: 10670 },
    { id: 'q7-10-inst-true-desc', text: 'if the statement agrees with the information', offset: 10675 },
    { id: 'q7-10-inst-false', text: 'FALSE', offset: 10720 },
    { id: 'q7-10-inst-false-desc', text: 'if the statement contradicts the information', offset: 10726 },
    { id: 'q7-10-inst-notgiven', text: 'NOT GIVEN', offset: 10772 },
    { id: 'q7-10-inst-notgiven-desc', text: 'if there is no information on this', offset: 10782 },
    { id: 'q7-num', text: '7', offset: 10820 },
    { id: 'q7-text', text: 'It is the female emperor penguin that carried more incubation duty.', offset: 10823 },
    { id: 'q8-num', text: '8', offset: 10892 },
    { id: 'q8-text', text: 'Evangelical Christian lives a similar lifestyle as penguin.', offset: 10895 },
    { id: 'q9-num', text: '9', offset: 10955 },
    { id: 'q9-text', text: 'With the advanced satellite photographs, fluctuation of penguin number is easily observed.', offset: 10958 },
    { id: 'q10-num', text: '10', offset: 11050 },
    { id: 'q10-text', text: 'Strong winds caused by Ozone depletion, blow away the sea ice.', offset: 11053 },
    // Q11-13 segments
    { id: 'q11-13-title', text: 'Questions 11-13', offset: 11120 },
    { id: 'q11-13-inst1', text: 'Complete the following summary of the paragraphs of Reading Passage, using NO MORE THAN TWO WORDS from the Reading Passage for each answer.', offset: 11136 },
    { id: 'q11-13-inst2', text: 'Write your answers in boxes 11-13 on your answer sheet.', offset: 11278 },
    { id: 'q11-13-part1', text: 'There are several reasons of why emperor penguins are vulnerable to sea ice transformation. First of all, they are not', offset: 11334 },
    { id: 'q11-13-part2', text: 'to walk on steep rocks that all over Antarctica. They wouldn\'t be able to breed. Next, emperors need to', offset: 11454 },
    { id: 'q11-13-part3', text: 'at certain time of year, which protects them from been killed by freezing water. Finally, emperor penguin\'s food called', offset: 11560 },
    { id: 'q11-13-part4', text: 'is also connected to availability of sea ice', offset: 11679 },
]);

// Convert plain text offset to HTML offset
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

const getPlainTextLength = (htmlText: string): number => {
    return htmlText.replace(/<[^>]*>/g, '').length;
};

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
    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentPlainTextLength = getPlainTextLength(plainText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;
    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);
    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;
    const annotations = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ];
    const sorted = annotations.sort((a, b) => b.start - a.start);
    let result = plainText;
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

// Expose methods for parent component
const getAnswers = () => {
    return { ...dragAnswers.value, ...answers.value };
};

watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
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

            if (selection && range) {
                // Find which text segment this selection belongs to
                // by checking which span element contains the selection
                let targetSpan = range.startContainer;

                // Traverse up to find the span with v-html
                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE) {
                    targetSpan = targetSpan.parentNode;
                }

                // Look for spans with text-gray-700, text-gray-800, text-gray-900, select-text, or text-lg classes (for passage)
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
                    // Get the text content of this span (this strips HTML tags automatically)
                    const spanText = (targetSpan as Element).textContent || '';

                    // Check if this is the main passage text (contains text-lg class)
                    const isPassageText = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500;

                    let segment = null;

                    if (isPassageText) {
                        // For passage text, use the passageText segment directly (index 3)
                        segment = textSegments.value[3];
                    } else {
                        // Find which segment this is by exact text match first, then fallback to contains
                        segment = textSegments.value.find((s) => s.text === spanText.trim());

                        // If exact match fails, try to find by checking if the span text contains the segment
                        if (!segment) {
                            segment = textSegments.value.find((s) => {
                                // Remove whitespace differences for comparison
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

                        // Use Range API to get exact offset within the container
                        const preSelectionRange = document.createRange();
                        preSelectionRange.selectNodeContents(targetSpan as Element);
                        preSelectionRange.setEnd(range.startContainer, range.startOffset);

                        // Get plain text offset (this is the offset in the rendered text)
                        const plainTextOffset = preSelectionRange.toString().length;

                        // Store plain text offsets - these will be used for both storage and rendering
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
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
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
    if (x - halfWidth < padding) x = halfWidth + padding;
    else if (x + halfWidth > viewportWidth - padding) x = viewportWidth - halfWidth - padding;
    if (y + modalHeight > viewportHeight - padding) {
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        if (y < padding) y = padding;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => { const input = document.querySelector<HTMLInputElement>('.note-input-field'); input?.focus(); }, 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter((note) => note.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
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
            showContextMenu.value = false;
        }
    } else {
        if (showDeleteTooltip.value) closeDeleteTooltip();
        if (showContextMenu.value) showContextMenu.value = false;
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
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
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
    if (relatedTarget?.closest('.note-hover-tooltip')) return;
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

const startResize = (event: MouseEvent) => { isResizing.value = true; event.preventDefault(); };
const handleResize = (event: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;
    if (newLeftWidth >= 20 && newLeftWidth <= 80) leftPanelWidth.value = newLeftWidth;
};
const stopResize = () => { isResizing.value = false; };

onMounted(async () => {
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
    <div ref="contentTextRef" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 1 Header -->
        <div class="border-b-0.5 part-header-box  mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 1')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 1–13.')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div ref="passagePanelRef" class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }" @mouseup="handleContentTextSelect" @click="handleContentClick">
                    <div class="px-6 py-1">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegment('The Last March of the Emperor Penguins')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span class="text-lg text-gray-700" data-segment-id="passage" v-html="getHighlightedSegment(textSegments[3].text)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div
                    class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize"
                    title="Drag to resize panels"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }" @mouseup="handleContentTextSelect" @click="handleContentClick">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ═══════════════════════════════════════════════════════════ -->
                            <!-- Questions 1-6: Drag and Drop Matching                      -->
                            <!-- ═══════════════════════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q1-6-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700 select-text" v-html="getHighlightedSegmentById('q1-6-inst1')"></p>
                                    <p class="mb-1 text-sm leading-relaxed text-gray-700 select-text" v-html="getHighlightedSegmentById('q1-6-inst2')"></p>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text" v-html="getHighlightedSegmentById('q1-6-inst3')"></p>
                                </div>

                                <!-- Side-by-side: Q1-6 slots on left, people bank on right -->
                                <div class="flex gap-4">

                                    <!-- Q1–Q6 Drop Slots -->
                                    <div class="flex-1 min-w-0 space-y-3">
                                        <template v-for="qNum in [1,2,3,4,5,6]" :key="qNum">
                                            <div
                                                :id="`question-${qNum}`"
                                                class="relative rounded border bg-white p-3 transition-colors"
                                                :class="hoveredQuestion === qNum ? 'border-gray-400' : 'border-gray-200'"
                                                @mouseenter="hoveredQuestion = qNum"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <!-- Question text -->
                                                <div class="mb-2">
                                                    <span
                                                        class="text-sm text-gray-800 select-text leading-snug"
                                                        v-html="getHighlightedSegmentById(`q${qNum}-text`)"
                                                    ></span>
                                                </div>

                                                <!-- Drop Zone on next line -->
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="flex items-center justify-center rounded border-2 border-dashed transition-colors"
                                                        style="min-width: 100px; min-height: 30px;"
                                                        :class="dragAnswers[`q${qNum}`]
                                                            ? 'border-gray-500 bg-white'
                                                            : 'border-gray-300 bg-gray-50 hover:border-gray-400 hover:bg-gray-100'"
                                                        @dragover="onDragOver"
                                                        @drop="(e) => onDropToSlot(e, `q${qNum}`)"
                                                    >
                                                        <template v-if="dragAnswers[`q${qNum}`]">
                                                            <div
                                                                class="flex items-center gap-1 px-2 cursor-grab active:cursor-grabbing"
                                                                draggable="true"
                                                                @dragstart="(e) => onDragStart(e, dragAnswers[`q${qNum}`], `q${qNum}`)"
                                                            >
                                                                <span class="text-sm font-bold text-gray-900">{{ dragAnswers[`q${qNum}`] }}</span>
                                                                <button
                                                                    @click.stop="removeFromSlot(`q${qNum}`)"
                                                                    class="flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-500"
                                                                    title="Remove"
                                                                >
                                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <template v-else>
                                                            <span class="text-sm font-bold text-gray-600">{{ qNum }}</span>
                                                        </template>
                                                    </div>

                                                    <!-- Flag button -->
                                                    <button
                                                        v-show="hoveredQuestion === qNum || isQuestionFlagged(qNum)"
                                                        @click.stop="toggleFlag(qNum)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(qNum) ? 'border-gray-400 bg-white text-red-500' : 'border-gray-300 bg-white text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                        :title="isQuestionFlagged(qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- People Bank (right side, sticky) -->
                                    <div
                                        class="shrink-0 flex flex-col gap-1.5 rounded border border-gray-300 bg-gray-50 p-2 self-start sticky top-2"
                                        style="width: 160px;"
                                        @dragover="onDragOver"
                                        @drop="onDropToBank"
                                    >
                                        <p class="text-xs font-semibold text-gray-500 text-center border-b border-gray-200 pb-1 mb-0.5">Options</p>
                                        <template v-for="person in peopleOptions" :key="person.id">
                                            <div
                                                draggable="true"
                                                @dragstart="(e) => onDragStart(e, person.id, 'bank')"
                                                class="flex items-center gap-1.5 rounded border px-2 py-1.5 text-sm font-medium transition-all select-none border-gray-400 bg-white text-gray-800 cursor-grab hover:border-gray-700 hover:bg-gray-100 active:cursor-grabbing active:opacity-70"
                                            >
                                                <span class="font-bold text-gray-900 shrink-0">{{ person.id }}</span>
                                                <span class="text-xs text-gray-600 truncate">{{ person.name }}</span>
                                            </div>
                                        </template>
                                    </div>

                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════ -->
                            <!-- Questions 7-10: TRUE / FALSE / NOT GIVEN                   -->
                            <!-- ═══════════════════════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-10-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-10-inst-choose')"></p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q7-10-inst-true')"></span>
                                        &nbsp;
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-10-inst-true-desc')"></span>
                                    </p>
                                    <p class="mb-1 text-sm text-gray-700">
                                        <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q7-10-inst-false')"></span>
                                        &nbsp;
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-10-inst-false-desc')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span class="font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q7-10-inst-notgiven')"></span>
                                        &nbsp;
                                        <span class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q7-10-inst-notgiven-desc')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 7 -->
                                    <div
                                        id="question-7"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 7"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q7-num')"></span>
                                            <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q7-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q7" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q7" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q7" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                            @click.stop="toggleFlag(7)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(7) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Question 8 -->
                                    <div
                                        id="question-8"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 8"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q8-num')"></span>
                                            <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q8-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q8" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q8" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q8" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                            @click.stop="toggleFlag(8)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(8) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Question 9 -->
                                    <div
                                        id="question-9"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 9"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q9-num')"></span>
                                            <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q9-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q9" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                            @click.stop="toggleFlag(9)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(9) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Question 10 -->
                                    <div
                                        id="question-10"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 10"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-2 flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900 select-text" v-html="getHighlightedSegmentById('q10-num')"></span>
                                            <span class="text-base text-gray-900 select-text" v-html="getHighlightedSegmentById('q10-text')"></span>
                                        </div>
                                        <div class="ml-6 space-y-2 select-none">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="TRUE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">TRUE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="FALSE" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">FALSE</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input type="radio" v-model="answers.q10" value="NOT GIVEN" class="h-4 w-4 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base text-gray-900">NOT GIVEN</span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                            @click.stop="toggleFlag(10)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(10) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════════════════════════════ -->
                            <!-- Questions 11-13: Summary Completion                        -->
                            <!-- ═══════════════════════════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700 select-text" v-html="getHighlightedSegmentById('q11-13-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('q11-13-inst1')"></p>
                                    <p class="mb-4 text-sm text-gray-700 select-text" v-html="getHighlightedSegmentById('q11-13-inst2')"></p>
                                </div>

                                <!-- Summary paragraph with inline blanks -->
                                <div class="rounded border border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-800">
                                    <span class="select-text" v-html="getHighlightedSegmentById('q11-13-part1')"></span>

                                    <!-- Blank 11 -->
                                    <span
                                        :id="`question-11`"
                                        class="relative inline-flex items-center"
                                        @mouseenter="hoveredQuestion = 11"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q11"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="11"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                            @click.stop="toggleFlag(11)"
                                            class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>

                                    <span class="select-text" v-html="getHighlightedSegmentById('q11-13-part2')"></span>

                                    <!-- Blank 12 -->
                                    <span
                                        :id="`question-12`"
                                        class="relative inline-flex items-center"
                                        @mouseenter="hoveredQuestion = 12"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q12"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="12"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                            @click.stop="toggleFlag(12)"
                                            class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>

                                    <span class="select-text" v-html="getHighlightedSegmentById('q11-13-part3')"></span>

                                    <!-- Blank 13 -->
                                    <span
                                        :id="`question-13`"
                                        class="relative inline-flex items-center"
                                        @mouseenter="hoveredQuestion = 13"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <input
                                            v-model="answers.q13"
                                            type="text"
                                            class="question-input mx-1 border border-gray-900 px-3 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                            style="width: 140px"
                                            placeholder="13"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocorrect="off"
                                            autocapitalize="off"
                                        />
                                        <button
                                            v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                            @click.stop="toggleFlag(13)"
                                            class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </span>

                                    <span class="select-text" v-html="getHighlightedSegmentById('q11-13-part4')"></span>
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
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
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
            class="fixed z-9999 w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
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
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 p-0.5.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 p-0.5.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
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
.passage-panel { width: 100%; }
.answer-panel { width: 100%; }

/* Prevent answer panel text from being selected during passage highlight */
.answer-panel * {
    -webkit-user-select: none;
    user-select: none;
}
/* Re-enable for explicitly selectable elements in answer panel */
.answer-panel .select-text,
.answer-panel input {
    -webkit-user-select: text;
    user-select: text;
}

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel { width: calc(100% - var(--panel-width) - 0.5rem); }
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

.highlight-nocolor { background-color: transparent; }
.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% { background-color: rgba(0, 0, 0, 0.1); transform: scale(1); }
    50% { background-color: rgba(0, 0, 0, 0.2); transform: scale(1.05); }
    100% { background-color: rgba(0, 0, 0, 0.1); transform: scale(1); }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

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

.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-icon { color: #6b7280; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
.note-hover-tooltip .note-delete-btn { color: #9ca3af; }
.note-hover-tooltip .note-delete-btn:hover { color: #ef4444; }
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

.question-input::placeholder {
    font-weight: 700;
    color: #374151;
}

.question-input-bold::placeholder {
    font-weight: 700;
    color: #374151;
}
</style>
