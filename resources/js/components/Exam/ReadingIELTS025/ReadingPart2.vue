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

// Drag and drop functionality for questions 14-18
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const speciesOptions = [
    { value: 'A', label: 'Homo neanderthalensis', fullText: 'A Homo neanderthalensis' },
    { value: 'B', label: 'Homo sapiens', fullText: 'B Homo sapiens' },
    { value: 'C', label: 'Both species', fullText: 'C Both species' },
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
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
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

// Get label from option value for display
const getSpeciesLabel = (value: string): string => {
    const option = speciesOptions.find((opt) => opt.value === value);
    return option ? option.label : '';
};

// Text segments with their cumulative offsets in the full text
const passageText = `Stress of Workplace
A

How busy is too busy? For some it means having to miss the occasional long lunch; for others it meansmissing lunch altogether. For a few, it is hot being able to take a “sickie” once a month. Then there is agroup of people for whom working every evening and weekend is normal, and franticness is the tempoof their lives. For most senior executives, workloads swing between extremely busy and frenzied. Thevice-president of the management consultancy AT Kearney and its head of telecommunications for theAsia-Pacific region, Neil Plumridge, says his work weeks vary from a “manageable” 45 hours to 80 hours,but average 60 hours.
<b</b>
B

Three warning signs alert Plumridge about his workload: sleep, scheduling and family. He knows he hastoo much on when he gets less than six hours of sleep for three consecutive nights; when he isconstantly having to reschedule appointments; “and the third one is on the family side”, says Plumridge,the father of a three-year-old daughter, and expecting a second child in October. “If I happen to miss abirthday or anniversary, I know things are out of control.” Being “too busy” is highly subjective. But forany individual, the perception of being too busy over a prolonged period can start showing up as stress:disturbed sleep, and declining mental and physical health. National workers’ compensation figures showstress causes the most lost time of any workplace injury. Employees suffering stress are off work anaverage of 16.6 weeks. The effects of stress are also expensive. Comcare, the Federal Governmentinsurer, reports that in 2003-04, claims for psychological injury accounted for 7% of claims but almost27% of claim costs. Experts say the key to dealing with stress is not to focus on relief—a game of golf ora massage-—but to reassess workloads. Neil Plumridge says he makes it a priority to work out what hasto change; that might mean allocating extra resources to a job, allowing more time or changingexpectations. The decision may take several days. He also relies on the advice of colleagues, saying hispeers coach each other with business problems. “Just a fresh pair of eyes over an issue can help,” he says.
<b></b>
C

Executive stress is not confined to big organisations. Vanessa Stoykov has been running her owadvertising and public relations business for seven years, specialising in work for financial anprofessional services firms. Evolution Media has grown so fast that it debuted on the BRW Fast 100 lisof fastest-growing small enterprises last year—just after Stoykov had her first child. Stoykov thrives othe mental stimulation of running her own business. “Like everyone, I have the occasional day when think my head’s going to blow off,” she says. Because of the growth phase the business is in, Stoykohas to concentrate on short-term stress relief—weekends in the mountains, the occasional “mentahealth” day—rather than delegating more work. She says: “We’re hiring more people, but you need ttrain them, teach them about the culture and the clients, so it’s actually more work rather than less.”

D

Identify the causes: Jan Eisner, Melbourne psychologist who specialises in executive coaching, saysthriving on a demanding workload is typical of senior executives and other high-potential businessadrenalin periods followed by quieter patches, while others thrive under sustained pressure. “We couldtake urine and blood hormonal measures and pass a judgement of whether someone’s physiologicallystressed or not,” she says. “But that’s not going to give us an indicator of what their experience of stress is, and what the emotional and cognitive impacts of stress are going to be.”
<b></b>
E
Eisner’s practice is informed by a movement known as positive psychology, a school of thought that argues “positive” experiences—feeling engaged, challenged, and that one is making a contribution to something meaningful—do not balance out negative ones such as stress; instead, they help people increase their resilience over time. Good stress, or positive experiences of being challenged and rewarded, is thus cumulative in the same way as bad stress. Eisner says many of the senior business people she coaches are relying more on regulating bad stress through methods such as meditation and yoga. She points to research showing that meditation can alter the biochemistry of the brain and actually help people “retrain” the way their brains and bodies react to stress. “Meditation and yoga enable you to shift the way that your brain reacts, so if you get proficient at it you’re in control.”
<b</b>
F

Recent research, such as last year’s study of public servants by the British epidemiologist Sir MichaelMarmot, shows the most important predictor of stress is the level of job control a person has. Thisdebunks the theory that stress is the prerogative of high-achieving executives with type-A personalitiesand crazy working hours. Instead, Marmot’s and other research reveals they have the best kind of job:one that combines high demands (challenging work) with high control (autonomy). “The worst jobs arethose that combine high demands and low control. People with demanding jobs but little autonomy haveup to four times the probability of depression and more than double the risk of heart disease,”La Montagne says. “Those two alone count for an enormous part of chronic diseases, and they representa potentially preventable part.” Overseas, particularly in Europe, such research is leading companies toredesign organisational practices to increase employees’ autonomy, cutting absenteeism and liftingproductivity.
G

The Australian vice-president of AT Kearney, Neil Plumridge says, “Often stress is caused by our settinunrealistic expectations of ourselves. I’ll promise a client I’ll do something tomorrow, and then [promiseanother client the same thing, when I really know it’s not going to happen. I’ve put stress on myself wheI could have said to the clients: Why don’t I give that to you in 48 hours?’ The client doesn’t care.Overcommitting is something people experience as an individual problem. We explain it as the result oprocrastination or Parkinson’s law: that work expands to fdl the time available. New research indicates that people may be hard-wired to do it.

H

A study in the February issue of the Journal of Experimental Psychology shows that people alwaysbelieve they will be less busy in the future than now. This is a misapprehension, according to the authorsof the report, Professor Gal Zauberman, of the University of North Carolina, and Professor John Lynch,of Duke University. “On average, an individual will be just as busy two weeks or a month from now as heor she is today. But that is not how it appears to be in everyday life,” they wrote. “People often makecommitments long in advance that they would never make if the same commitments required immediateaction. That is, they discount future time investments relatively steeply.” Why do we perceive a greater“surplus” of time in the future than in the present? The researchers suggest that people underestimatecompletion times for tasks stretching into the future, and that they are bad at imagining futurecompetition for their time.`;

const textSegments = ref([
    // Part 2 header text segments (negative offsets to come before passage)
    { id: 'part-header', text: 'Part 2', offset: -100 },
    { id: 'part-instructions', text: 'Read the text and answer questions 14–26.', offset: -93 },
    { id: 'header-title', text: 'Stress of Workplace', offset: -51 },

    // Single passage text segment
    {
        id: 'passage',
        text: passageText,
        offset: 0,
    },

    // =========================
    // Questions 14–18 (Matching)
    // =========================
    { id: 'q14-18-title', text: 'Questions 14-18', offset: 4800 },

    {
        id: 'q14-18-instructions',
        text: 'Look at the following statements (Questions 14-18) and the list of people below. Match each statement with the correct person, A-D. Write the correct letter, A-D in boxes 14-18 on your answer sheet. NB You may use any letter more than once.',
        offset: 4815,
    },

    // Question numbers 14–18
    { id: 'q14-num', text: '14.', offset: 5050 },
    { id: 'q14', text: 'Work stress usually happens in the high level of a business.', offset: 5055 },

    { id: 'q15-num', text: '15.', offset: 5100 },
    { id: 'q15', text: 'More people involved would be beneficial for stress relief.', offset: 5105 },

    { id: 'q16-num', text: '16.', offset: 5150 },
    { id: 'q16', text: 'Temporary holiday sometimes doesn’t mean less work.', offset: 5155 },

    { id: 'q17-num', text: '17.', offset: 5200 },
    { id: 'q17', text: 'Stress leads to a wrong direction when trying to satisfy customers.', offset: 5205 },

    { id: 'q18-num', text: '18.', offset: 5255 },
    { id: 'q18', text: 'It is commonly accepted that stress at present is more severe than in the future.', offset: 5260 },

    // List of People
    { id: 'people-title', text: 'List of People', offset: 5310 },

    { id: 'person-a', text: 'A Jan Eisner', offset: 5325 },
    { id: 'person-b', text: 'B Vanessa Stoykov', offset: 5350 },
    { id: 'person-c', text: 'C Gal Zauberman', offset: 5375 },
    { id: 'person-d', text: 'D Neil Plumridge', offset: 5400 },

    // =========================
    // Questions 19–21 (MCQ)
    // =========================
    { id: 'q19-21-title', text: 'Questions 19-21', offset: 5440 },

    {
        id: 'q19-21-instructions',
        text: 'Choose the correct letter, A, B, C or D. Write the correct letter in boxes 19-21 on your answer sheet.',
        offset: 5455,
    },

    // Question 19
    { id: 'q19-num', text: '19.', offset: 5580 },
    {
        id: 'q19',
        text: 'Which of the following workplace stress is NOT mentioned according to Plumridge in the following options?',
        offset: 5585,
    },
    { id: 'q19-a', text: 'A not enough time spent on family', offset: 5700 },
    { id: 'q19-b', text: 'B unable to concentrate on work', offset: 5735 },
    { id: 'q19-c', text: 'C inadequate time of sleep', offset: 5770 },
    { id: 'q19-d', text: 'D alteration of appointment', offset: 5800 },

    // Question 20
    { id: 'q20-num', text: '20.', offset: 5850 },
    {
        id: 'q20',
        text: 'Which of the following solution is NOT mentioned in helping reduce the work pressure according to Plumridge?',
        offset: 5855,
    },
    { id: 'q20-a', text: 'A allocate more personnels', offset: 5980 },
    { id: 'q20-b', text: 'B increase more time', offset: 6010 },
    { id: 'q20-c', text: 'C lower expectation', offset: 6040 },
    { id: 'q20-d', text: 'D do sports and massage', offset: 6070 },

    // Question 21
    { id: 'q21-num', text: '21.', offset: 6120 },
    { id: 'q21', text: 'What is the point of view of Jan Eisner towards work stress?', offset: 6125 },
    {
        id: 'q21-a',
        text: 'A Medical test can only reveal part of the data needed to cope with stress',
        offset: 6200,
    },
    { id: 'q21-b', text: 'B Index of body samples plays determined role.', offset: 6275 },
    { id: 'q21-c', text: 'C Emotional affection is superior to physical one.', offset: 6325 },
    { id: 'q21-d', text: 'D One well designed solution can release all stress.', offset: 6370 },

    // =========================
    // Questions 22–26 (Summary)
    // =========================
    { id: 'q22-26-title', text: 'Questions 22-26', offset: 6425 },

    {
        id: 'q22-26-instructions',
        text: 'Complete the summary below. Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer. Write your answers in boxes 22-26 on your answer sheet.',
        offset: 6440,
    },

    {
        id: 'summary-intro',
        text: 'Statistics from National worker’s compensation indicate stress plays the most important role in',
        offset: 6600,
    },

    { id: 'q22', text: '22 ................', offset: 6685 },

    { id: 'summary-mid1', text: 'Staffs take about', offset: 6710 },

    { id: 'q23', text: '23 ................', offset: 6725 },

    {
        id: 'summary-mid2',
        text: 'for absence from work caused by stress. Not just time is our main concern but great expenses generated consequently. An official insurer wrote sometime that about',
        offset: 6755,
    },

    { id: 'q24', text: '24 ................', offset: 6930 },

    {
        id: 'summary-mid3',
        text: 'of all claims were mental issues whereas nearly 27% costs in all claims. Sports such as',
        offset: 6960,
    },

    { id: 'q25', text: '25 ................', offset: 7050 },

    { id: 'summary-mid4', text: ', as well as', offset: 7075 },

    { id: 'q26', text: '26 ................', offset: 7090 },

    {
        id: 'summary-end',
        text: 'could be a treatment to release stress; however, specialists recommended another practical way out: analyse workloads once again.',
        offset: 7120,
    },
]);

// Convert plain text offset to HTML offset
const plainToHtmlOffset = (htmlText: string, plainOffset: number): number => {
    let plainIndex = 0;
    let htmlIndex = 0;

    while (plainIndex < plainOffset && htmlIndex < htmlText.length) {
        if (htmlText[htmlIndex] === '<') {
            // Skip HTML tag entirely
            while (htmlIndex < htmlText.length && htmlText[htmlIndex] !== '>') {
                htmlIndex++;
            }
            htmlIndex++; // Skip closing '>'
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
    // Find this segment's offset
    const segment = textSegments.value.find((s) => s.text === segmentText);
    if (!segment) return segmentText;

    const segmentOffset = segment.offset;
    // Use plain text length for comparison
    const segmentPlainTextLength = getPlainTextLength(segmentText);
    const segmentEnd = segmentOffset + segmentPlainTextLength;

    // Check if any highlights overlap with this segment (using plain text offsets)
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return segmentText;
    }

    // Combine highlights and notes into a single list of annotations
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

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = segmentText;

    for (const annotation of sorted) {
        // Calculate the plain text position within this segment
        const plainStart = Math.max(0, annotation.start - segmentOffset);
        const plainEnd = Math.min(segmentPlainTextLength, annotation.end - segmentOffset);

        if (plainStart < plainEnd) {
            // Convert plain text offsets to HTML offsets
            const htmlStart = plainToHtmlOffset(result, plainStart);
            const htmlEnd = plainToHtmlOffset(result, plainEnd);

            const before = result.substring(0, htmlStart);
            const annotated = result.substring(htmlStart, htmlEnd);
            const after = result.substring(htmlEnd);

            if (annotation.type === 'note') {
                // Simple mark tag - tooltip is rendered via Teleport on hover
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
            // Position tooltip ABOVE the selection with arrow pointing down
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 50; // Approximate tooltip height
            const menuY = rect.top - menuHeight - 8; // 8px gap above selection

            const viewportWidth = window.innerWidth;
            const menuWidth = 140; // Smaller width for new design

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10), // Ensure it doesn't go above viewport
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
                    const isPassageText = (targetSpan as Element).classList?.contains('text-lg') || spanText.length > 500; // Passage is long

                    let segment = null;

                    if (isPassageText) {
                        // For passage text, use the passageText segment directly (index 2)
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

                        console.log('Selection:', { selected: normalizedSelected, startOffset, endOffset, plainTextOffset, isPassageText });
                    }
                }
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

    // Set minimum and maximum widths (20% to 80%)
    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

// Load saved answers when component mounts
onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);

    // Add resize event listeners
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 2 Header -->
        <div class="mx-5 mt-4 border-b-0.5 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" v-html="getHighlightedSegment('Part 2')"></p>
            <p class="text-sm text-gray-700 select-text" v-html="getHighlightedSegment('Read the text and answer questions 14–26.')"></p>
        </div>

        <div class="mx-auto px-3 py-1 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="p-6">
                        <h2 class="text-xl font-bold">
                            <span class="text-gray-900 select-text" v-html="getHighlightedSegment('Workplace Stress')"></span>
                        </h2>
                    </div>
                    <div class="space-y-6 p-6" :style="contentZoom">
                        <div class="space-y-6 text-sm leading-relaxed select-text">
                            <div class="">
                                <div class="text-justify leading-relaxed text-gray-700">
                                    <span
                                        class="text-lg text-gray-700"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment(textSegments[3].text)"
                                    ></span>
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
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-sm">
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

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ========================= -->
                            <!-- Questions 14-18 (Matching) -->
                            <!-- ========================= -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q14-18-title"
                                                v-html="getHighlightedSegment('Questions 14-18')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            data-segment-id="q14-18-instructions"
                                            v-html="getHighlightedSegment('Look at the following statements (Questions 14-18) and the list of people below. Match each statement with the correct person, A-D. Write the correct letter, A-D in boxes 14-18 on your answer sheet. NB You may use any letter more than once.')"
                                        ></span>
                                    </p>
                                </div>

                                <!-- Questions 14-18 -->
                                <div class="space-y-4">

                                    <!-- Q14 -->
                                    <div
                                        id="question-14"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 14"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('14.')"></span>
                                            <select
                                                v-model="answers.q14"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q14"
                                                    v-html="getHighlightedSegment('Work stress usually happens in the high level of a business.')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                            @click.stop="toggleFlag(14)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q15 -->
                                    <div
                                        id="question-15"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 15"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('15.')"></span>
                                            <select
                                                v-model="answers.q15"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q15"
                                                    v-html="getHighlightedSegment('More people involved would be beneficial for stress relief.')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                            @click.stop="toggleFlag(15)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q16 -->
                                    <div
                                        id="question-16"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 16"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('16.')"></span>
                                            <select
                                                v-model="answers.q16"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q16"
                                                    v-html="getHighlightedSegment('Temporary holiday sometimes doesn\'t mean less work.')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                            @click.stop="toggleFlag(16)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q17 -->
                                    <div
                                        id="question-17"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 17"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('17.')"></span>
                                            <select
                                                v-model="answers.q17"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q17"
                                                    v-html="getHighlightedSegment('Stress leads to a wrong direction when trying to satisfy customers.')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q18 -->
                                    <div
                                        id="question-18"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 18"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('18.')"></span>
                                            <select
                                                v-model="answers.q18"
                                                class="w-24 flex-shrink-0 border-2 border-gray-900 bg-white px-3 py-1 text-sm transition-colors focus:border-black focus:bg-white focus:outline-none"
                                            >
                                                <option value="">Select</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q18"
                                                    v-html="getHighlightedSegment('It is commonly accepted that stress at present is more severe than in the future.')"
                                                ></span>
                                            </p>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- List of People (static reference) -->
                                <div class="mt-6 border border-gray-900 bg-white p-4 w-fit">
                                    <h4 class="mb-2 font-bold text-gray-900 text-sm">
                                        <span data-segment-id="people-title" v-html="getHighlightedSegment('List of People')"></span>
                                    </h4>
                                    <div class="space-y-1 text-sm">
                                        <p><span data-segment-id="person-a" v-html="getHighlightedSegment('A Jan Eisner')"></span></p>
                                        <p><span data-segment-id="person-b" v-html="getHighlightedSegment('B Vanessa Stoykov')"></span></p>
                                        <p><span data-segment-id="person-c" v-html="getHighlightedSegment('C Gal Zauberman')"></span></p>
                                        <p><span data-segment-id="person-d" v-html="getHighlightedSegment('D Neil Plumridge')"></span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- ========================= -->
                            <!-- Questions 19-21 (MCQ)     -->
                            <!-- ========================= -->
                            <div class="bg-white p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q19-21-title"
                                                v-html="getHighlightedSegment('Questions 19-21')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            data-segment-id="q19-21-instructions"
                                            v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D. Write the correct letter in boxes 19-21 on your answer sheet.')"
                                        ></span>
                                    </p>
                                </div>

                                <div class="space-y-8">

                                    <!-- Q19 -->
                                    <div
                                        id="question-19"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 19"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('19.')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q19"
                                                    v-html="getHighlightedSegment('Which of the following workplace stress is NOT mentioned according to Plumridge in the following options?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="A" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q19-a" v-html="getHighlightedSegment('A not enough time spent on family')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="B" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q19-b" v-html="getHighlightedSegment('B unable to concentrate on work')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="C" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q19-c" v-html="getHighlightedSegment('C inadequate time of sleep')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q19" value="D" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q19-d" v-html="getHighlightedSegment('D alteration of appointment')"></span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="toggleFlag(19)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q20 -->
                                    <div
                                        id="question-20"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 20"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('20.')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q20"
                                                    v-html="getHighlightedSegment('Which of the following solution is NOT mentioned in helping reduce the work pressure according to Plumridge?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="A" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q20-a" v-html="getHighlightedSegment('A allocate more personnels')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="B" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q20-b" v-html="getHighlightedSegment('B increase more time')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="C" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q20-c" v-html="getHighlightedSegment('C lower expectation')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q20" value="D" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q20-d" v-html="getHighlightedSegment('D do sports and massage')"></span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="toggleFlag(20)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q21 -->
                                    <div
                                        id="question-21"
                                        class="relative bg-white p-2"
                                        @mouseenter="hoveredQuestion = 21"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-2 mb-3">
                                            <span class="font-bold text-gray-900 select-text flex-shrink-0" v-html="getHighlightedSegment('21.')"></span>
                                            <p class="text-sm leading-relaxed text-gray-700">
                                                <span
                                                    class="text-gray-700"
                                                    data-segment-id="q21"
                                                    v-html="getHighlightedSegment('What is the point of view of Jan Eisner towards work stress?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="ml-6 space-y-2">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="A" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q21-a" v-html="getHighlightedSegment('A Medical test can only reveal part of the data needed to cope with stress')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="B" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q21-b" v-html="getHighlightedSegment('B Index of body samples plays determined role.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="C" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q21-c" v-html="getHighlightedSegment('C Emotional affection is superior to physical one.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input type="radio" v-model="answers.q21" value="D" class="mt-0.5 flex-shrink-0" />
                                                <span class="text-sm text-gray-700" data-segment-id="q21-d" v-html="getHighlightedSegment('D One well designed solution can release all stress.')"></span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                            @click.stop="toggleFlag(21)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ========================= -->
                            <!-- Questions 22-26 (Summary) -->
                            <!-- ========================= -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span
                                                class="text-gray-700"
                                                data-segment-id="q22-26-title"
                                                v-html="getHighlightedSegment('Questions 22-26')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-700">
                                        <span
                                            class="text-gray-700"
                                            data-segment-id="q22-26-instructions"
                                            v-html="getHighlightedSegment('Complete the summary below. Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer. Write your answers in boxes 22-26 on your answer sheet.')"
                                        ></span>
                                    </p>
                                </div>

                                <div class="bg-white p-4">
                                    <div class="text-sm leading-relaxed text-gray-700 space-y-1">

                                        <!-- Summary paragraph with inline blanks -->
                                        <p class="flex flex-wrap items-baseline gap-x-1 gap-y-2">

                                            <!-- summary-intro -->
                                            <span
                                                data-segment-id="summary-intro"
                                                v-html="getHighlightedSegment('Statistics from National worker\'s compensation indicate stress plays the most important role in')"
                                            ></span>

                                            <!-- Q22 -->
                                            <span
                                                id="question-22"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 22"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q22"
                                                    type="text"
                                                    class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                    style="width: 140px"
                                                    placeholder="22"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                                    @click.stop="toggleFlag(22)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- summary-mid1 -->
                                            <span
                                                data-segment-id="summary-mid1"
                                                v-html="getHighlightedSegment('Staffs take about')"
                                            ></span>

                                            <!-- Q23 -->
                                            <span
                                                id="question-23"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 23"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q23"
                                                    type="text"
                                                    class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                    style="width: 140px"
                                                    placeholder="23"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                                    @click.stop="toggleFlag(23)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- summary-mid2 -->
                                            <span
                                                data-segment-id="summary-mid2"
                                                v-html="getHighlightedSegment('for absence from work caused by stress. Not just time is our main concern but great expenses generated consequently. An official insurer wrote sometime that about')"
                                            ></span>

                                            <!-- Q24 -->
                                            <span
                                                id="question-24"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 24"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q24"
                                                    type="text"
                                                    class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                    style="width: 140px"
                                                    placeholder="24"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                                    @click.stop="toggleFlag(24)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- summary-mid3 -->
                                            <span
                                                data-segment-id="summary-mid3"
                                                v-html="getHighlightedSegment('of all claims were mental issues whereas nearly 27% costs in all claims. Sports such as')"
                                            ></span>

                                            <!-- Q25 -->
                                            <span
                                                id="question-25"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 25"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q25"
                                                    type="text"
                                                    class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                    style="width: 140px"
                                                    placeholder="25"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                                    @click.stop="toggleFlag(25)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- summary-mid4 -->
                                            <span
                                                data-segment-id="summary-mid4"
                                                v-html="getHighlightedSegment(', as well as')"
                                            ></span>

                                            <!-- Q26 -->
                                            <span
                                                id="question-26"
                                                class="relative inline-flex items-center gap-1"
                                                @mouseenter="hoveredQuestion = 26"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <input
                                                    v-model="answers.q26"
                                                    type="text"
                                                    class="border border-gray-900 bg-white px-2 py-0.5 text-center font-bold placeholder:font-bold placeholder:text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                                                    style="width: 140px"
                                                    placeholder="26"
                                                />
                                                <button
                                                    v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                                    @click.stop="toggleFlag(26)"
                                                    class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                    :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- summary-end -->
                                            <span
                                                data-segment-id="summary-end"
                                                v-html="getHighlightedSegment('could be a treatment to release stress; however, specialists recommended another practical way out: analyse workloads once again.')"
                                            ></span>

                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>


    <!-- Context Menu for Highlighting -->
    <!-- Context Menu for Highlighting - Speech Bubble Style -->
    <Teleport to="body">
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-9998">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${contextMenuPosition.x}px`,
                    top: `${contextMenuPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button
                        @click="openNoteInput"
                        class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                        title="Add Note"
                    >
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z"
                            />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button
                        @click="applyHighlight('yellow')"
                        class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50"
                        title="Highlight"
                    >
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

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-9998" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-9999"
                :style="{
                    left: `${deleteTooltipPosition.x}px`,
                    top: `${deleteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button
                        @click="confirmDeleteHighlight"
                        type="button"
                        class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-9999"
                :style="{
                    left: `${noteTooltipPosition.x}px`,
                    top: `${noteTooltipPosition.y}px`,
                    transform: 'translateX(-50%)',
                }"
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
            :style="{
                left: `${noteInputPosition.x}px`,
                top: `${noteInputPosition.y}px`,
                transform: 'translateX(-50%)',
            }"
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
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">
                    Save Note
                </button>
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
</style>
