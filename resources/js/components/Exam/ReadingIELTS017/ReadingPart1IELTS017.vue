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
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

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

const passageText = `<span class="font-bold text-lg" >California's age of Megafires</span>

<b>A.</b> There's a reason fire squads now battling more than a dozen blazes in southern California are having such difficulty containing the flames, despite better preparedness than ever and decades of experience fighting fires fanned by the notorious Santa Ana winds. The wildfires themselves, experts say, generally are hotter, move faster, and spread more erratically than is the past.

<b>B.</b> The short-term explanation is that the region, which usually has dry summers, has had nine inches less rain than normal this year. Longer term, climate change across the West is leading to hotter days on average and longer fire seasons. Experts say this is likely to yield more megafires like the conflagrations that this week forced evacuations of at least 300,000 resident in California's southland and led President Bush to declare a disaster emergency in seven counties on Tuesday.

<b>C.</b> Megafires, also called "siege fires," are the increasingly frequent blazes that burn 500,000 acres or more - 10 times the size of the average forest fire of 20 years ago. One of the current wildfires is the sixth biggest in California ever, in terms of acreage burned, according to state figures and news reports. The trend to more superhot fires, experts say, has been driven by a century-long policy of the US Forest Service to stop wildfires as quickly as possible. The unintentional consequence was to halt the natural eradication of underbrush, now the primary fuel for megafires. Three other factors contribute to the trend, they add. First is climate change marked by a 1-degree F. rise in average yearly temperature across the West. Second is a fire season that on average is 78 days longer than in the late 1980s. Third is increased building of homes and other structures in wooded areas.

<b>D.</b> "We are increasingly building our homes ... in fire-prone ecosystems," says Dominik Kulakowski, adjunct professor of biology at Clark University Graduate School of Geography in Worcester, Mass. Doing that "in many of the forests of the Western US ... is like building homes on the side of an active volcano." In California, where population growth has averaged more than 600,000 a year for at least a decade, housing has pushed into such areas. "What once was open space is now residential homes providing fuel to make fires burn with greater intensity," says Terry McHale of the California Department of Forestry firefighters union. "With so much dryness, so many communities to catch fire, so many fronts to fight, it becomes an almost incredible job."

<b>E.</b> That said, many experts give California high marks for making progress on preparedness since 2003, when the largest fires in state history scorched 750,000 acres, burned 3,640 homes, and killed 22 people. Stung then by criticism of bungling that allowed fires to spread when they might have been contained, personnel are meeting the peculiar challenges of neighborhood - and canyon-hopping fires better than in recent years, observers say.

<b>F.</b> State promises to provide newer engines, planes, and helicopters have been fulfilled. Firefighters unions that then complained of dilapidated equipment, old fire engines, and insufficient blueprints for fire safety are now praising the state's commitment, noting that funding for firefighting has increased despite huge cuts in many other programs. "We are pleased that the Schwarzenegger administration has been very proactive in its support of us and come through with budgetary support of the infrastructure needs we have long sought," says Mr. McHale with the firefighters union.

<b>G.</b> Besides providing money to upgrade the fire engines that must traverse the mammoth state and wind along serpentine canyon roads, the state has invested in better command-and-control facilities as well as the strategies to run them. "In the fire sieges of earlier years, we found out that we had the willingness of mutual-aid help from other jurisdictions and states, but we were not able to communicate adequately with them," says Kim Zagaris, chief of the state's Office of Emergency Services, fire and rescue branch. After a 2004 blue-ribbon commission examined and revamped those procedures, the statewide response "has become far more professional and responsive," he says.

<b>H.</b> Besides ordering the California National Guard on Monday to make 1,500 guardsmen available for firefighting efforts, Gov. Arnold Schwarzenegger asked the Pentagon to send all available Modular Airborne Fighting Systems to the area. The military Lockheed C-130 cargo/utility aircraft carry a pressurized 3,000-gallon tank that can eject fire retardant or water in fewer than five seconds through two tubes at the rear of the plane. This load can cover an area 1/4-mile long and 60 feet wide to create a fire barrier. Governor Schwarzenegger also directed 2,300 inmate firefighters and 170 custody staff from the California Department of Corrections and Rehabilitation to work hand in hand with state and local firefighters.

<b>I.</b> Residents and government officials alike are noting the improvements with gratitude, even amid the loss of homes, churches, businesses, and farms. By Tuesday morning, the fires had burned 1,200 homes and businesses and set 245,957 acres - 384 square miles ablaze. Despite such losses, there is a sense that the speed, dedication, and coordination of firefighters from several states and jurisdictions are resulting in greater efficiency than is past "siege fire" situations.

<b>J.</b> "I am extraordinarily impressed by the improvements we have witnessed between the last big fire and this," says Ross Simmons, a San Diego-based lawyer who had to evacuate both his home and business on Monday, taking up residence at a Hampton Inn 30 miles south of his home in Rancho Bernardo. After fires consumed 172,000 acres there in 2003, the San Diego region turned communitywide soul-searching into improved building codes, evacuation procedures, and procurement of new technology. Mr. Simmons and neighbors began receiving automated phone calls at 3:30 a.m. Monday morning telling them to evacuate. "Nothwithstanding all the damage that will be caused by this, we will not come close to the loss of life because of what we have ... put in place since then," he says.`;

// ── Text segments keyed by unique id ─────────────────────────────────────────
// Each entry has { id, text, offset }.  We look up by id in getHighlightedSegmentById.
// For the passage we keep the old text-keyed helper as well (used for v-html passage block).

const passageOffset = passageText.length;

// All segments — note every text value must be unique within this array,
// OR we look them up by id (preferred going forward).
const textSegmentList = ref([
    // passage
    { id: 'passage',           text: passageText,                                                                                                       offset: 0 },
    // Q1-6 instructions
    { id: 'q1-6-title',        text: 'Questions 1-6',                                                                                                   offset: passageOffset + 1 },
    { id: 'q1-6-inst1',        text: 'Complete the following summary of the paragraphs of Reading Passage',                                             offset: passageOffset + 15 },
    { id: 'q1-6-inst2',        text: 'Using NO MORE THAN TWO WORDS from the Reading Passage for each answer.',                                          offset: passageOffset + 80 },
    { id: 'q1-6-inst3',        text: 'Write your answers in boxes 1-6 on your answer sheet.',                                                           offset: passageOffset + 145 },
    // summary text chunks
    { id: 'q1-part1',          text: 'Experts point out that blazes in California are having more heat, faster speed and they',                          offset: passageOffset + 210 },
    { id: 'q1-part2',          text: "more unpredictably compared with former ones. One explanation is that California's summer is dry,",               offset: passageOffset + 310 },
    { id: 'q2-part1',          text: 'is below the average point. Another long term explanation is that hotter and longer potential days occur due to',  offset: passageOffset + 420 },
    { id: 'q4-part1',          text: 'Nowadays, Megafires burn',                                                                                        offset: passageOffset + 520 },
    { id: 'q4-part2',          text: 'the size of forest area caused by an ordinary fire of 20 years ago. The serious trend is mainly caused by well-grown underbrush, which provides', offset: passageOffset + 570 },
    { id: 'q5-part1',          text: 'for the siege fires. Other contributors are climate change and extended',                                          offset: passageOffset + 720 },
    // Q7-9 header
    { id: 'q7-9-title',        text: 'Questions 7-9',                                                                                                   offset: passageOffset + 800 },
    { id: 'q7-9-inst1',        text: 'Choose the correct letter A, B, C or D.',                                                                        offset: passageOffset + 814 },
    { id: 'q7-9-inst2',        text: 'Write your answers in boxes 7-9 on your answer sheet.',                                                           offset: passageOffset + 855 },
    // Q7
    { id: 'q7-question',       text: "What is expert's attitude towards California's performance after 2003 megafire?",                                  offset: passageOffset + 920 },
    { id: 'q7-optionA',        text: 'They could have done better',                                                                                     offset: passageOffset + 1000 },
    { id: 'q7-optionB',        text: 'Blamed them on casualties',                                                                                       offset: passageOffset + 1035 },
    { id: 'q7-optionC',        text: 'Improvement made on preparation',                                                                                 offset: passageOffset + 1065 },
    { id: 'q7-optionD',        text: 'Serious criticism',                                                                                               offset: passageOffset + 1100 },
    // Q8
    { id: 'q8-question',       text: 'According to Governor Schwarzenegger, which one is CORRECT about his effort for firefighting?',                   offset: passageOffset + 1125 },
    { id: 'q8-optionA',        text: 'Schwarzenegger requested successfully for military weapons',                                                       offset: passageOffset + 1225 },
    { id: 'q8-optionB',        text: 'Schwarzenegger led many prison management staff to work together with local fire fighters',                        offset: passageOffset + 1290 },
    { id: 'q8-optionC',        text: 'Schwarzenegger acted negatively in recent megafire in California',                                                offset: passageOffset + 1380 },
    { id: 'q8-optionD',        text: 'Schwarzenegger ordered 1,500 office clerks to join firefighting scene.',                                          offset: passageOffset + 1450 },
    // Q9
    { id: 'q9-question',       text: 'What happened to Ross Simmon on the day of megafire break out?',                                                  offset: passageOffset + 1525 },
    { id: 'q9-optionA',        text: 'He was sleeping till morning',                                                                                    offset: passageOffset + 1590 },
    { id: 'q9-optionB',        text: 'He was doing business at Hampton Inn',                                                                            offset: passageOffset + 1625 },
    { id: 'q9-optionC',        text: 'He suffered employee death on that morning',                                                                      offset: passageOffset + 1665 },
    { id: 'q9-optionD',        text: 'He was alarmed by machine calls',                                                                                 offset: passageOffset + 1710 },
    // Q10-13 header
    { id: 'q10-13-title',      text: 'Questions 10-13',                                                                                                 offset: passageOffset + 1745 },
    { id: 'q10-13-inst1',      text: 'Do the following statements agree with the information given in Reading Passage 1?',                              offset: passageOffset + 1760 },
    { id: 'q10-13-inst2',      text: 'In boxes 10-13 on your answer sheet, write:',                                                                     offset: passageOffset + 1845 },
    { id: 'q10-13-true-label', text: 'TRUE — if the statement agrees with the information',                                                             offset: passageOffset + 1900 },
    { id: 'q10-13-false-label',text: 'FALSE — if the statement contradicts the information',                                                            offset: passageOffset + 1955 },
    { id: 'q10-13-ng-label',   text: 'NOT GIVEN — if there is no information on this in the passage',                                                  offset: passageOffset + 2015 },
    // statements
    { id: 'q10-statement',     text: 'The area of open space in California has declined during the past decade.',                                        offset: passageOffset + 2080 },
    { id: 'q11-statement',     text: 'Fire squad wants to recruit more firefighters this year.',                                                         offset: passageOffset + 2250 },
    { id: 'q12-statement',     text: 'Fire fighters union declared that firefighters have had more improved and supportive facility by the local government.', offset: passageOffset + 2310 },
    { id: 'q13-statement',     text: 'Before the year of 2004, well coordination and communication between California and other states already existed in fire siege.', offset: passageOffset + 2440 },
    // TRUE / FALSE / NOT GIVEN option labels (unique per question to avoid lookup collision)
    { id: 'q10-true',  text: 'TRUE-q10',        offset: passageOffset + 2560 },
    { id: 'q10-false', text: 'FALSE-q10',        offset: passageOffset + 2570 },
    { id: 'q10-ng',    text: 'NOT GIVEN-q10',   offset: passageOffset + 2580 },
    { id: 'q11-true',  text: 'TRUE-q11',        offset: passageOffset + 2600 },
    { id: 'q11-false', text: 'FALSE-q11',        offset: passageOffset + 2610 },
    { id: 'q11-ng',    text: 'NOT GIVEN-q11',   offset: passageOffset + 2620 },
    { id: 'q12-true',  text: 'TRUE-q12',        offset: passageOffset + 2640 },
    { id: 'q12-false', text: 'FALSE-q12',        offset: passageOffset + 2650 },
    { id: 'q12-ng',    text: 'NOT GIVEN-q12',   offset: passageOffset + 2660 },
    { id: 'q13-true',  text: 'TRUE-q13',        offset: passageOffset + 2680 },
    { id: 'q13-false', text: 'FALSE-q13',        offset: passageOffset + 2690 },
    { id: 'q13-ng',    text: 'NOT GIVEN-q13',   offset: passageOffset + 2700 },
]);

// ── Annotation helpers ────────────────────────────────────────────────────────
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

/** Look up by id (preferred — avoids duplicate-text collisions) */
const getHighlightedSegmentById = (id: string): string => {
    const segment = textSegmentList.value.find(s => s.id === id);
    if (!segment) return '';
    return applyAnnotationsToSegment(segment.text, segment.offset);
};

/** Legacy lookup by text content (used for the passage block) */
const getHighlightedSegment = (segmentText: string): string => {
    const segment = textSegmentList.value.find(s => s.text === segmentText);
    if (!segment) return segmentText;
    return applyAnnotationsToSegment(segment.text, segment.offset);
};

const applyAnnotationsToSegment = (segmentText: string, segmentOffset: number): string => {
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    const overlappingHighlights = highlights.value.filter(h => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter(n => n.start < segmentEnd && n.end > segmentOffset);

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return segmentText;

    const annotations = [
        ...overlappingHighlights.map(h => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map(n => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id })),
    ].sort((a, b) => b.start - a.start);

    let result = segmentText;
    for (const annotation of annotations) {
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);
        if (plainStart >= plainEnd) continue;
        const htmlStart = plainToHtmlOffset(result, plainStart);
        const htmlEnd = plainToHtmlOffset(result, plainEnd);
        const before = result.substring(0, htmlStart);
        const annotated = result.substring(htmlStart, htmlEnd);
        const after = result.substring(htmlEnd);
        result = annotation.type === 'note'
            ? `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`
            : `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
    }
    return result;
};

// ── Expose / watch ────────────────────────────────────────────────────────────
const getAnswers = () => ({ ...answers.value });

watch(leftPanelWidth, v => localStorage.setItem(PANEL_WIDTH_KEY, v.toString()));

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 1 && questionNumber <= 6) targetId = 'question-1-6';
    else if (questionNumber >= 7 && questionNumber <= 9) targetId = 'question-7-9';
    else if (questionNumber >= 10 && questionNumber <= 13) targetId = 'question-10-13';
    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

const scrollToHighlight = async (highlightId: number) => {
    await nextTick();
    const element = document.querySelector(`mark[data-highlight-id="${highlightId}"]`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-flash');
        setTimeout(() => element.classList.remove('highlight-flash'), 2000);
    }
};

// ── Selection / highlight logic ───────────────────────────────────────────────
const handleContentTextSelect = () => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();
        if (!selected) { showContextMenu.value = false; return; }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && contentTextRef.value) {
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50;
            const menuY = rect.top - menuHeight - 8;
            const vw = window.innerWidth;
            const menuWidth = 140;
            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), vw - menuWidth / 2 - 10),
                y: Math.max(menuY, 10),
            };
            showContextMenu.value = true;

            if (selection && range) {
                let targetSpan: Node | null = range.startContainer;
                while (targetSpan && targetSpan.nodeType !== Node.ELEMENT_NODE) targetSpan = targetSpan.parentNode;
                while (targetSpan && !(targetSpan as Element).hasAttribute?.('data-segment-id')) {
                    const parent = targetSpan.parentNode;
                    if (!parent || parent === contentTextRef.value) break;
                    targetSpan = parent;
                }

                if (targetSpan && (targetSpan as Element).hasAttribute?.('data-segment-id')) {
                    const segmentId = (targetSpan as Element).getAttribute('data-segment-id');
                    let segment = segmentId === 'passage'
                        ? textSegmentList.value[0]
                        : textSegmentList.value.find(s => s.id === segmentId);

                    if (segment) {
                        const preRange = document.createRange();
                        preRange.selectNodeContents(targetSpan as Element);
                        preRange.setEnd(range.startContainer, range.startOffset);
                        const plainTextOffset = preRange.toString().length;
                        const startOffset = segment.offset + plainTextOffset;
                        const endOffset = startOffset + selected.length;
                        selectedText.value = selected;
                        selectionRange.value = { start: startOffset, end: endOffset };
                    }
                }
            }
        }
    }, 10);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter(n => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const openNoteInput = () => {
    if (!selectionRange.value || !selectedText.value) return;
    const modalWidth = 320; const modalHeight = 180; const padding = 10;
    const selection = window.getSelection();
    let x: number, y: number;
    if (selection && selection.rangeCount > 0) {
        const r = selection.getRangeAt(0).getBoundingClientRect();
        x = r.left + r.width / 2; y = r.bottom + 10;
    } else {
        x = contextMenuPosition.value.x; y = contextMenuPosition.value.y + 70;
    }
    const vw = window.innerWidth; const vh = window.innerHeight;
    const hw = modalWidth / 2;
    if (x - hw < padding) x = hw + padding;
    else if (x + hw > vw - padding) x = vw - hw - padding;
    if (y + modalHeight > vh - padding) {
        if (selection?.rangeCount) y = selection.getRangeAt(0).getBoundingClientRect().top - modalHeight - 10;
        if (y < padding) y = padding;
    }
    noteInputPosition.value = { x, y };
    showNoteInput.value = true;
    showContextMenu.value = false;
    setTimeout(() => document.querySelector<HTMLInputElement>('.note-input-field')?.focus(), 100);
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const { start: ns, end: ne } = selectionRange.value;
    findOverlappingHighlights(ns, ne).forEach(h => deleteHighlight(h.id));
    notes.value = notes.value.filter(n => !(n.start < ne && n.end > ns));
    notes.value.push({ id: Date.now(), text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: ns, end: ne });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const cancelNote = () => { noteInputText.value = ''; showNoteInput.value = false; };
const deleteNote = (id: number) => { notes.value = notes.value.filter(n => n.id !== id); };
const closeDeleteTooltip = () => { showDeleteTooltip.value = false; highlightToDelete.value = null; };

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') { showContextMenu.value = false; closeDeleteTooltip(); }
};

const handleContentClick = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark[data-highlight-id]') as HTMLElement;
    if (mark) {
        const id = mark.getAttribute('data-highlight-id');
        if (id) {
            event.stopPropagation();
            const rect = mark.getBoundingClientRect();
            deleteTooltipPosition.value = { x: rect.left + rect.width / 2, y: rect.bottom + 8 };
            highlightToDelete.value = parseInt(id, 10);
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
        deleteHighlight(highlightToDelete.value);
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
    }
};

const handleNoteMouseEnter = (event: MouseEvent) => {
    const mark = (event.target as HTMLElement).closest('mark.note-highlight[data-note-id]') as HTMLElement;
    if (mark) {
        const noteId = mark.getAttribute('data-note-id');
        if (noteId) {
            const id = parseInt(noteId, 10);
            const note = notes.value.find(n => n.id === id);
            if (note) {
                const rect = mark.getBoundingClientRect();
                let y = rect.top - 58;
                if (y < 10) y = rect.bottom + 8;
                noteTooltipPosition.value = { x: rect.left + rect.width / 2, y };
                hoveredNoteId.value = id;
                hoveredNoteText.value = note.note;
                showNoteTooltip.value = true;
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

const startResize = (e: MouseEvent) => { isResizing.value = true; e.preventDefault(); };
const handleResize = (e: MouseEvent) => {
    if (!isResizing.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const pct = ((e.clientX - rect.left) / rect.width) * 100;
    if (pct >= 20 && pct <= 80) leftPanelWidth.value = pct;
};
const stopResize = () => { isResizing.value = false; };

watch(notes, newNotes => emit('notesChange', newNotes), { deep: true });

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

defineExpose({ getAnswers, scrollToQuestion, scrollToHighlight, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">

        <!-- Part 1 Header — colour #F1F2EC -->
        <div class="border-b-0.5 mx-5 mt-4 border-gray-400 px-4 py-2" style="background-color: #F1F2EC;">
            <p class="text-sm font-bold text-gray-900 select-text">READING PASSAGE 1</p>
            <p class="text-sm text-gray-700 select-text">You should spend about 20 minutes on Questions 1-13 which are based on Reading Passage 1 below.</p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage ──────────────────────────────────── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="p-4">
                                <div class="text-justify leading-relaxed text-gray-700 whitespace-pre-wrap"
                                    data-segment-id="passage"
                                    v-html="getHighlightedSegment(passageText)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Resize Handle ────────────────────────────────────── -->
                <div class="group relative hidden w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100 lg:flex"
                    @mousedown="startResize" title="Drag to resize panels">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 shadow-sm">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Section ────────────────────────────────── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto"
                    :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8" :style="contentZoom">

                            <!-- ══ Questions 1-6 ══ -->
                            <div id="question-1-6" class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q1-6-title"
                                                v-html="getHighlightedSegmentById('q1-6-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q1-6-inst1" v-html="getHighlightedSegmentById('q1-6-inst1')"></span><br />
                                        <span data-segment-id="q1-6-inst2" v-html="getHighlightedSegmentById('q1-6-inst2')"></span><br />
                                        <span data-segment-id="q1-6-inst3" v-html="getHighlightedSegmentById('q1-6-inst3')"></span>
                                    </p>
                                </div>

                                <!-- Summary paragraph with inline inputs -->
                                <div class="space-y-4 bg-white p-4 border border-gray-200 rounded">
                                    <p class="text-gray-700 leading-relaxed">
                                        <span data-segment-id="q1-part1"
                                            v-html="getHighlightedSegmentById('q1-part1')"></span>

                                        <!--
                                            FIX: Q1-6 bookmark button blink fix.
                                            We use the same pattern as document 3 (Q23-26):
                                            - The outer <span> carries @mouseenter/@mouseleave so the hovered state
                                              is set on the QUESTION level, not on individual children.
                                            - The bookmark button uses CSS opacity transition (opacity-0 → opacity-100)
                                              instead of v-show, which avoids the re-render flash caused by v-show
                                              toggling visibility as the mouse moves between the input and button.
                                        -->

                                        <!-- Q1 -->
                                        <span id="question-1"
                                            class="inline-flex items-center align-middle relative mx-1"
                                            @mouseenter="hoveredQuestion = 1"
                                            @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q1" type="text"
                                                class="question-input inline-block border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px" placeholder="1"
                                                spellcheck="false" autocomplete="off" />
                                            <button
                                                class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0 ml-1"
                                                :class="[
                                                    isQuestionFlagged(1)
                                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    (hoveredQuestion === 1 || isQuestionFlagged(1)) ? 'opacity-100' : 'opacity-0'
                                                ]"
                                                @click.stop="toggleFlag(1)"
                                                :title="isQuestionFlagged(1) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q1-part2"
                                            v-html="getHighlightedSegmentById('q1-part2')"></span>

                                        <!-- Q2 -->
                                        <span id="question-2"
                                            class="inline-flex items-center align-middle relative mx-1"
                                            @mouseenter="hoveredQuestion = 2"
                                            @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q2" type="text"
                                                class="question-input inline-block border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px" placeholder="2"
                                                spellcheck="false" autocomplete="off" />
                                            <button
                                                class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0 ml-1"
                                                :class="[
                                                    isQuestionFlagged(2)
                                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    (hoveredQuestion === 2 || isQuestionFlagged(2)) ? 'opacity-100' : 'opacity-0'
                                                ]"
                                                @click.stop="toggleFlag(2)"
                                                :title="isQuestionFlagged(2) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q2-part1"
                                            v-html="getHighlightedSegmentById('q2-part1')"></span>

                                        <!-- Q3 -->
                                        <span id="question-3"
                                            class="inline-flex items-center align-middle relative mx-1"
                                            @mouseenter="hoveredQuestion = 3"
                                            @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q3" type="text"
                                                class="question-input inline-block border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px" placeholder="3"
                                                spellcheck="false" autocomplete="off" />
                                            <button
                                                class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0 ml-1"
                                                :class="[
                                                    isQuestionFlagged(3)
                                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    (hoveredQuestion === 3 || isQuestionFlagged(3)) ? 'opacity-100' : 'opacity-0'
                                                ]"
                                                @click.stop="toggleFlag(3)"
                                                :title="isQuestionFlagged(3) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q4-part1"
                                            v-html="getHighlightedSegmentById('q4-part1')"></span>

                                        <!-- Q4 -->
                                        <span id="question-4"
                                            class="inline-flex items-center align-middle relative mx-1"
                                            @mouseenter="hoveredQuestion = 4"
                                            @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q4" type="text"
                                                class="question-input inline-block border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px" placeholder="4"
                                                spellcheck="false" autocomplete="off" />
                                            <button
                                                class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0 ml-1"
                                                :class="[
                                                    isQuestionFlagged(4)
                                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    (hoveredQuestion === 4 || isQuestionFlagged(4)) ? 'opacity-100' : 'opacity-0'
                                                ]"
                                                @click.stop="toggleFlag(4)"
                                                :title="isQuestionFlagged(4) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q4-part2"
                                            v-html="getHighlightedSegmentById('q4-part2')"></span>

                                        <!-- Q5 -->
                                        <span id="question-5"
                                            class="inline-flex items-center align-middle relative mx-1"
                                            @mouseenter="hoveredQuestion = 5"
                                            @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q5" type="text"
                                                class="question-input inline-block border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px" placeholder="5"
                                                spellcheck="false" autocomplete="off" />
                                            <button
                                                class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0 ml-1"
                                                :class="[
                                                    isQuestionFlagged(5)
                                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    (hoveredQuestion === 5 || isQuestionFlagged(5)) ? 'opacity-100' : 'opacity-0'
                                                ]"
                                                @click.stop="toggleFlag(5)"
                                                :title="isQuestionFlagged(5) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>

                                        <span data-segment-id="q5-part1"
                                            v-html="getHighlightedSegmentById('q5-part1')"></span>

                                        <!-- Q6 -->
                                        <span id="question-6"
                                            class="inline-flex items-center align-middle relative mx-1"
                                            @mouseenter="hoveredQuestion = 6"
                                            @mouseleave="hoveredQuestion = null">
                                            <input v-model="answers.q6" type="text"
                                                class="question-input inline-block border border-gray-900 px-2 py-0.5 text-center text-sm focus:border-black focus:outline-none"
                                                style="width: 200px" placeholder="6"
                                                spellcheck="false" autocomplete="off" />
                                            <button
                                                class="inline-flex h-6 w-6 items-center justify-center rounded border transition-all shrink-0 ml-1"
                                                :class="[
                                                    isQuestionFlagged(6)
                                                        ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600',
                                                    (hoveredQuestion === 6 || isQuestionFlagged(6)) ? 'opacity-100' : 'opacity-0'
                                                ]"
                                                @click.stop="toggleFlag(6)"
                                                :title="isQuestionFlagged(6) ? 'Remove bookmark' : 'Bookmark this question'">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- ══ Questions 7-9 ══ -->
                            <div id="question-7-9" class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q7-9-title"
                                                v-html="getHighlightedSegmentById('q7-9-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q7-9-inst1"
                                            v-html="getHighlightedSegmentById('q7-9-inst1')"></span>
                                    </p>
                                    <p class="mb-4 text-sm text-gray-700">
                                        <span data-segment-id="q7-9-inst2"
                                            v-html="getHighlightedSegmentById('q7-9-inst2')"></span>
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <!-- Question 7 -->
                                    <div id="question-7" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 7" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">7.</span>
                                            <div class="min-w-0 flex-1">
                                                <!--
                                                    FIX: Q7 options C and D highlight problem.
                                                    Root cause: the old code used getHighlightedSegment(text) which
                                                    does a text-content lookup. 'Improvement made on preparation' and
                                                    'Serious criticism' are short strings; if ANY other segment shares
                                                    the same text (or the lookup falls through to the passage), the
                                                    wrong offset is used and the highlight appears in the wrong place.
                                                    Fix: use getHighlightedSegmentById('q7-optionC') etc., which looks
                                                    up by unique id, guaranteeing the correct offset every time.
                                                -->
                                                <p class="mb-2 text-base font-semibold text-gray-900"
                                                    data-segment-id="q7-question"
                                                    v-html="getHighlightedSegmentById('q7-question')"></p>
                                                <button
                                                    v-show="hoveredQuestion === 7 || isQuestionFlagged(7)"
                                                    @click.stop="toggleFlag(7)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(7) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="space-y-1 ml-4">
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q7" value="A" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q7-optionA"
                                                            v-html="getHighlightedSegmentById('q7-optionA')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q7" value="B" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q7-optionB"
                                                            v-html="getHighlightedSegmentById('q7-optionB')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q7" value="C" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q7-optionC"
                                                            v-html="getHighlightedSegmentById('q7-optionC')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q7" value="D" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q7-optionD"
                                                            v-html="getHighlightedSegmentById('q7-optionD')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 8 -->
                                    <div id="question-8" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 8" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">8.</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="mb-2 text-base font-semibold text-gray-900"
                                                    data-segment-id="q8-question"
                                                    v-html="getHighlightedSegmentById('q8-question')"></p>
                                                <button
                                                    v-show="hoveredQuestion === 8 || isQuestionFlagged(8)"
                                                    @click.stop="toggleFlag(8)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(8) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="space-y-1 ml-4">
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q8" value="A" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q8-optionA"
                                                            v-html="getHighlightedSegmentById('q8-optionA')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q8" value="B" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q8-optionB"
                                                            v-html="getHighlightedSegmentById('q8-optionB')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q8" value="C" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q8-optionC"
                                                            v-html="getHighlightedSegmentById('q8-optionC')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q8" value="D" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q8-optionD"
                                                            v-html="getHighlightedSegmentById('q8-optionD')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Question 9 -->
                                    <div id="question-9" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 9" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">9.</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="mb-2 text-base font-semibold text-gray-900"
                                                    data-segment-id="q9-question"
                                                    v-html="getHighlightedSegmentById('q9-question')"></p>
                                                <button
                                                    v-show="hoveredQuestion === 9 || isQuestionFlagged(9)"
                                                    @click.stop="toggleFlag(9)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(9) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="space-y-1 ml-4">
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q9" value="A" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q9-optionA"
                                                            v-html="getHighlightedSegmentById('q9-optionA')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q9" value="B" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q9-optionB"
                                                            v-html="getHighlightedSegmentById('q9-optionB')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q9" value="C" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q9-optionC"
                                                            v-html="getHighlightedSegmentById('q9-optionC')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-start gap-1 rounded p-1 hover:bg-gray-50">
                                                        <input type="radio" v-model="answers.q9" value="D" class="mt-0.5 h-4 w-4" />
                                                        <span class="text-base" data-segment-id="q9-optionD"
                                                            v-html="getHighlightedSegmentById('q9-optionD')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ══ Questions 10-13 ══ -->
                            <div id="question-10-13" class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-gray-700" data-segment-id="q10-13-title"
                                                v-html="getHighlightedSegmentById('q10-13-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q10-13-inst1"
                                            v-html="getHighlightedSegmentById('q10-13-inst1')"></span>
                                    </p>
                                    <p class="mb-2 text-sm leading-relaxed text-gray-700">
                                        <span data-segment-id="q10-13-inst2"
                                            v-html="getHighlightedSegmentById('q10-13-inst2')"></span>
                                    </p>
                                    <div class="space-y-1 text-sm">
                                        <p><span data-segment-id="q10-13-true-label"
                                            v-html="getHighlightedSegmentById('q10-13-true-label')"></span></p>
                                        <p><span data-segment-id="q10-13-false-label"
                                            v-html="getHighlightedSegmentById('q10-13-false-label')"></span></p>
                                        <p><span data-segment-id="q10-13-ng-label"
                                            v-html="getHighlightedSegmentById('q10-13-ng-label')"></span></p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Statement 10 -->
                                    <div id="question-10" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 10" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">10.</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="mb-2 text-base text-gray-900" data-segment-id="q10-statement"
                                                    v-html="getHighlightedSegmentById('q10-statement')"></p>
                                                <button v-show="hoveredQuestion === 10 || isQuestionFlagged(10)"
                                                    @click.stop="toggleFlag(10)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(10) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="flex space-x-4 mt-2">
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q10" value="TRUE" class="h-4 w-4" />
                                                        <span class="text-sm">TRUE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q10" value="FALSE" class="h-4 w-4" />
                                                        <span class="text-sm">FALSE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q10" value="NOT GIVEN" class="h-4 w-4" />
                                                        <span class="text-sm">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Statement 11 -->
                                    <div id="question-11" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 11" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">11.</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="mb-2 text-base text-gray-900" data-segment-id="q11-statement"
                                                    v-html="getHighlightedSegmentById('q11-statement')"></p>
                                                <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                                    @click.stop="toggleFlag(11)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="flex space-x-4 mt-2">
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q11" value="TRUE" class="h-4 w-4" />
                                                        <span class="text-sm">TRUE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q11" value="FALSE" class="h-4 w-4" />
                                                        <span class="text-sm">FALSE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q11" value="NOT GIVEN" class="h-4 w-4" />
                                                        <span class="text-sm">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Statement 12 -->
                                    <div id="question-12" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">12.</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="mb-2 text-base text-gray-900" data-segment-id="q12-statement"
                                                    v-html="getHighlightedSegmentById('q12-statement')"></p>
                                                <button v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                                    @click.stop="toggleFlag(12)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="flex space-x-4 mt-2">
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q12" value="TRUE" class="h-4 w-4" />
                                                        <span class="text-sm">TRUE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q12" value="FALSE" class="h-4 w-4" />
                                                        <span class="text-sm">FALSE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q12" value="NOT GIVEN" class="h-4 w-4" />
                                                        <span class="text-sm">NOT GIVEN</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Statement 13 -->
                                    <div id="question-13" class="relative bg-white p-3"
                                        @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                        <div class="flex items-start gap-2">
                                            <span class="text-base font-bold text-gray-900">13.</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="mb-2 text-base text-gray-900" data-segment-id="q13-statement"
                                                    v-html="getHighlightedSegmentById('q13-statement')"></p>
                                                <button v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                                    @click.stop="toggleFlag(13)"
                                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                                <div class="flex space-x-4 mt-2">
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q13" value="TRUE" class="h-4 w-4" />
                                                        <span class="text-sm">TRUE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q13" value="FALSE" class="h-4 w-4" />
                                                        <span class="text-sm">FALSE</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1 cursor-pointer">
                                                        <input type="radio" v-model="answers.q13" value="NOT GIVEN" class="h-4 w-4" />
                                                        <span class="text-sm">NOT GIVEN</span>
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
    </div>

    <Teleport to="body">
        <!-- Highlight context menu -->
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
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete highlight tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }">
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

        <!-- Note hover tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
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

        <!-- Note input modal -->
        <div v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop>
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
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
                    class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.select-text { user-select: text; -webkit-user-select: text; }
.select-none { user-select: none; -webkit-user-select: none; cursor: col-resize; }

.passage-panel { width: 100%; }
.answer-panel { width: 100%; }

@media (min-width: 1024px) {
    .passage-panel { width: calc(var(--panel-width) - 0.5rem); }
    .answer-panel { width: calc(100% - var(--panel-width) - 0.5rem); }
}

mark[data-highlight-id] { padding: 2px 0; border-radius: 2px; cursor: pointer; color: inherit; }

.highlight-yellow  { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green   { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue    { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink    { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange  { background-color: rgba(254, 215, 170, 0.5); }

.highlight-question {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}
@keyframes highlightPulse {
    0%   { background-color: rgba(0,0,0,0.1); transform: scale(1); }
    50%  { background-color: rgba(0,0,0,0.2); transform: scale(1.05); }
    100% { background-color: rgba(0,0,0,0.1); transform: scale(1); }
}

.highlight-flash { animation: flashHighlight 1.5s ease-out; }
@keyframes flashHighlight {
    0%   { background-color: yellow; }
    100% { background-color: transparent; }
}

.overflow-y-auto::-webkit-scrollbar { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #111827; }

.highlight-tooltip .tooltip-arrow {
    position: absolute; left: 50%; bottom: -8px; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.highlight-tooltip .tooltip-arrow::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    border-left: 9px solid transparent; border-right: 9px solid transparent; border-top: 9px solid #d1d5db;
    z-index: -1;
}
.delete-highlight-tooltip .tooltip-arrow-up {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid white;
    filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.1));
}
.delete-highlight-tooltip .tooltip-arrow-up::before {
    content: ''; position: absolute; left: -9px; top: 1px;
    border-left: 9px solid transparent; border-right: 9px solid transparent; border-bottom: 9px solid #d1d5db;
    z-index: -1;
}
.note-hover-tooltip .tooltip-arrow-down {
    position: relative; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid white;
    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
}
.note-hover-tooltip .tooltip-arrow-down::before {
    content: ''; position: absolute; left: -9px; bottom: 1px;
    border-left: 9px solid transparent; border-right: 9px solid transparent; border-top: 9px solid #d1d5db;
    z-index: -1;
}
.note-hover-tooltip .note-tooltip-content { max-width: 240px; }
.note-hover-tooltip .note-tooltip-text { line-height: 1.4; }
</style>

<style>
.note-highlight {
    background-color: rgba(191, 219, 254, 0.6) !important;
    cursor: pointer; padding: 2px 0; border-radius: 2px;
}
.note-highlight:hover { background-color: rgba(147, 197, 253, 0.7) !important; }

.question-input::placeholder { font-weight: 700; color: #374151; }
</style>