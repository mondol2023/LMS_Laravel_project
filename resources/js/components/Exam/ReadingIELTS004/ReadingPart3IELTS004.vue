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

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const draggedOption = ref<string | null>(null);

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Reading Part 1 component

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

// Delete highlight tooltip
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const highlightToDelete = ref<number | null>(null);

// Note tooltip (hover)
const showNoteTooltip = ref(false);
const noteTooltipPosition = ref({ x: 0, y: 0 });
const hoveredNoteId = ref<number | null>(null);
const hoveredNoteText = ref('');

// Note functionality
const showNoteInput = ref(false);
const noteInputText = ref('');
const noteInputPosition = ref({ x: 0, y: 0 });
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number; part: string }>>([]);


// Drag and drop functionality for questions 28-33
const draggedHeading = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);


// Filter out used heading options

const handleDragStart = (e: DragEvent, option: string) => {
    languageOptions.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedHeading.value = null;
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
    const option = e.dataTransfer?.getData('text/plain') || draggedHeading.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedHeading.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};

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

const multipleAnswers = ref<{
    q33_35: string[];
}>({
    q33_35: [],
});


watch(
    () => multipleAnswers.value.q33_35,
    (vals) => {
        answers.value.q33 = vals[0] || '';
        answers.value.q34 = vals[1] || '';
        answers.value.q35 = vals[2] || '';
    }
);

// Text segments with their cumulative offsets in the full text
const passageText =
    ref(`There is nothing unusual about a single language dying. Communities have come and gone throughout history, and with them their language. But what is happening today is extraordinary, judged by the standards of the past. It is language extinction on a massive scale. According to the best estimates, there are some 6,000 languages in the world. Of these, about half are going to die out in the course of the next century: that’s 3,000 languages in 1,200 months. On average, there is a language dying out somewhere in the world every two weeks or so.

How do we know? In the course of the past two or three decades, linguists all over the world have been gathering comparative data. If they find a language with just a few speakers left, and nobody is bothering to pass the language on to the children, they conclude that language is bound to die out soon. And we have to draw the same conclusion if a language has less than 100 speakers. It is not likely to last very long. A 1999 survey shows that 97 per cent of the world’s languages are spoken by just four per cent of the people.

It is too late to do anything to help many languages, where the speakers are too few or too old, and where the community is too busy just trying to survive to care about their language. But many languages are not in such a serious position. Often, where languages are seriously endangered, there are things that can be done to give new life to them. It is called revitalisation.

Once a community realises that its language is in danger, it can start to introduce measures which can genuinely revitalise. The community itself must want to save its language. The culture of which it is a part must need to have a respect for minority languages. There needs to be funding, to support courses, materials, and teachers. And there need to be linguists, to get on with the basic task of putting the language down on paper. That’s the bottom line: getting the language documented - recorded, analysed, written down. People must be able to read and write if they and their language are to have a future in an increasingly computer-literate civilisation.

But can we save a few thousand languages, just like that? Yes, if the will and funding were available. It is not cheap, getting linguists into the field, training local analysts, supporting the community with language resources and teachers, compiling grammars and dictionaries, writing materials for use in schools. It takes time, lots of it, to revitalise an endangered language. Conditions vary so much that it is difficult to generalise, but a figure of $100,000 a year per language cannot be far from the truth. If we devoted that amount of effort over three years for each of 3,000 languages, we would be talking about some $900 million.

There are some famous cases which illustrate what can be done. Welsh, alone among the Celtic languages, is not only stopping its steady decline towards extinction but showing signs of real growth. Two Language Acts protect the status of Welsh now, and its presence is increasingly in evidence wherever you travel in Wales.

On the other side of the world, Maori in New Zealand has been maintained by a system of so-called ‘language nests’, first introduced in 1982. These are organisations which provide children under five with a domestic setting in which they are intensively exposed to the language. The staff are all Maori speakers from the local community. The hope is that the children will keep their Maori skills alive after leaving the nests, and that as they grow older they will, in turn, become role models to a new generation of young children. There are cases like this all over the world. And when the reviving language is associated with a degree of political autonomy, the growth
can be especially striking, as shown by Faroese, spoken in the Faroe Islands, after the Islanders received a measure of autonomy from Denmark.

In Switzerland, Romansch was facing a difficult situation, spoken in five very different dialects, with small and diminishing numbers, as young people left their community for work in the German-speaking cities. The solution here was the creation in the 1980s of a unified written language for all these dialects. Romansch Grischun, as it is now called, has official status in parts of Switzerland, and is being increasingly used in spoken form on radio and television.

A language can be brought back from the very brink of extinction. The Ainu language of Japan, after many years of neglect and repression, had reached a stage where there were only eight fluent speakers left, all elderly. However, new government policies brought fresh attitudes and a positive interest in survival. Several ‘semi- speakers’ - people who had become unwilling to speak Ainu because of the negative attitudes by Japanese speakers - were prompted to become active speakers again. There is fresh interest now and the language is more publicly available than it has been for years.

If good descriptions and materials are available, even extinct languages can be resurrected. Kaurna, from South Australia, is an example. This language had been extinct for about a century, but had been quite well documented. So, when a strong movement grew for its revival, it was possible to reconstruct it. The revised language is not the same as the original, of course. It lacks the range that the original had, and much of the old vocabulary. But it can nonetheless act as a badge of present-day identity for its people. And as long as people continue to value it as a true marker of their identity, and are prepared to keep using it, it will develop new functions and new vocabulary, as any other living language would do.

It is too soon to predict the future of these revived languages, but in some parts of the world they are attracting precisely the range of positive attitudes and grass roots support which are the preconditions for language survival. In such unexpected but heart-warming ways might we see the grand total of languages in the world minimally increased.`);

// All static texts in the component, in order of appearance, for offset calculation
const allSegments = [
    // ── Part 3 Header ──────────────────────────────────────────────────────────
    { id: 'part-header',       text: 'Part 3' },
    { id: 'part-instructions', text: 'Read the text and answer questions 28-40.' },

    // ── Passage Panel ──────────────────────────────────────────────────────────
    { id: 'passage-title', text: 'SAVING LANGUAGE' },
    { id: 'passage',       text: passageText.value },


    // ── Questions 28-32 (YES / NO / NOT GIVEN) ────────────────────────────────
    { id: 'q28-32-title',       text: 'Questions 28–32' },
    { id: 'q28-32-instructions',text: 'Do the following statements agree with the views of the writer in' },
    { id: 'q28-32-passage-ref', text: 'Reading Passage 3?' },
    { id: 'q28-32-in-boxes',    text: 'In boxes' },
    { id: 'q28-32-range',       text: '28–32' },
    { id: 'q28-32-write',       text: 'on your answer sheet write:' },
    { id: 'yes-label',          text: 'YES' },
    { id: 'yes-desc',           text: 'if the statement agrees with the writer\u2019s views' },
    { id: 'no-label',           text: 'NO' },
    { id: 'no-desc',            text: 'if the statement contradicts the writer\u2019s views' },
    { id: 'not-given-label',    text: 'NOT GIVEN' },
    { id: 'not-given-desc',     text: 'if it is impossible to say what the writer thinks about this' },

    // Question 28
    { id: 'q28-num',       text: '28' },
    { id: 'q28',           text: 'The rate at which languages are becoming extinct has increased.' },
    { id: 'q28-yes',       text: 'YES' },
    { id: 'q28-no',        text: 'NO' },
    { id: 'q28-not-given', text: 'NOT GIVEN' },

    // Question 29
    { id: 'q29-num',       text: '29' },
    { id: 'q29',           text: 'Research on the subject of language extinction began in the 1990s.' },
    { id: 'q29-yes',       text: 'YES' },
    { id: 'q29-no',        text: 'NO' },
    { id: 'q29-not-given', text: 'NOT GIVEN' },

    // Question 30
    { id: 'q30-num',       text: '30' },
    { id: 'q30',           text: 'In order to survive, a language needs to be spoken by more than 100 people.' },
    { id: 'q30-yes',       text: 'YES' },
    { id: 'q30-no',        text: 'NO' },
    { id: 'q30-not-given', text: 'NOT GIVEN' },

    // Question 31
    { id: 'q31-num',       text: '31' },
    { id: 'q31',           text: 'Certain parts of the world are more vulnerable than others to language extinction.' },
    { id: 'q31-yes',       text: 'YES' },
    { id: 'q31-no',        text: 'NO' },
    { id: 'q31-not-given', text: 'NOT GIVEN' },

    // Question 32
    { id: 'q32-num',       text: '32' },
    { id: 'q32',           text: 'Saving language should be the major concern of any small community whose language is under threat.' },
    { id: 'q32-yes',       text: 'YES' },
    { id: 'q32-no',        text: 'NO' },
    { id: 'q32-not-given', text: 'NOT GIVEN' },

    // ── Questions 33-35 (Checkbox – pick THREE factors) ───────────────────────
    { id: 'q33-35-title',           text: 'Questions 33-35' },
    { id: 'q33-35-instructions',    text: 'The list below gives some of the factors that are necessary to assist the revitalisation of a language within a community.' },
    { id: 'q33-35-which',           text: 'Which ' },
    { id: 'q33-35-three',           text: 'THREE' },
    { id: 'q33-35-factors-mentioned', text: ' of the factors are mentioned by the writer of the text?' },
    { id: 'q33-35-write',           text: 'Write the appropriate letters ' },
    { id: 'q33-35-letters',         text: 'A\u2013G' },
    { id: 'q33-35-in-boxes',        text: ' in boxes 33\u201335 on your answer sheet.' },
    { id: 'q33-35-list-header',     text: 'List of factors:' },
    { id: 'q33-35-option-a',        text: 'A. the existence of related languages' },
    { id: 'q33-35-option-b',        text: 'B. support from the indigenous population' },
    { id: 'q33-35-option-c',        text: 'C. books tracing the historical development of the language' },
    { id: 'q33-35-option-d',        text: 'D. on-the-spot help from language experts' },
    { id: 'q33-35-option-e',        text: 'E. a range of speakers of different ages' },
    { id: 'q33-35-option-f',        text: 'F. formal education procedures' },
    { id: 'q33-35-option-g',        text: 'G. a common purpose for which the language is required' },

    // ── Questions 36-40 (Drag-and-drop – match language to statement) ─────────
    { id: 'q36-40-title',        text: 'Questions 36-40' },
    { id: 'q36-40-instructions', text: 'Match the languages A\u2013F with the statements below which describe how a language was saved.' },
    { id: 'q36-40-write',        text: 'Write your answers in boxes 36\u201340 on your answer sheet.' },

    // Language option labels (rendered in the draggable panel)
    { id: 'lang-a', text: 'A. Welsh' },
    { id: 'lang-b', text: 'B. Maori' },
    { id: 'lang-c', text: 'C. Faroese' },
    { id: 'lang-d', text: 'D. Romansch' },
    { id: 'lang-e', text: 'E. Ainu' },
    { id: 'lang-f', text: 'F. Kauma' },

    // Question 36
    { id: 'q36-num', text: '36' },
    { id: 'q36',     text: 'The region in which the language was spoken gained increased independence.' },

    // Question 37
    { id: 'q37-num', text: '37' },
    { id: 'q37',     text: 'People were encouraged to view the language with less prejudice.' },

    // Question 38
    { id: 'q38-num', text: '38' },
    { id: 'q38',     text: 'Language immersion programmes were set up for sectors of the population.' },

    // Question 39
    { id: 'q39-num', text: '39' },
    { id: 'q39',     text: 'A merger of different varieties of the language took place.' },

    // Question 40
    { id: 'q40-num', text: '40' },
    { id: 'q40',     text: 'Written samples of the language permitted its revitalisation.' },
];

// ── Build textSegments with cumulative offsets ────────────────────────────────

const textSegments = computed(() => {
    let currentOffset = 0;

    return allSegments.map((segment) => {
        const text = typeof segment.text === 'string'
            ? segment.text
            : segment.text.value; // handle ref safely

        const result = {
            id: segment.id,
            text,
            offset: currentOffset,
        };

        currentOffset += text.length;
        return result;
    });
});

// ── Q33-35 checkbox options (bound to `segmentId` for highlighting) ───────────
const q33_35Options = [
    { value: 'A', segmentId: 'q33-35-option-a' },
    { value: 'B', segmentId: 'q33-35-option-b' },
    { value: 'C', segmentId: 'q33-35-option-c' },
    { value: 'D', segmentId: 'q33-35-option-d' },
    { value: 'E', segmentId: 'q33-35-option-e' },
    { value: 'F', segmentId: 'q33-35-option-f' },
    { value: 'G', segmentId: 'q33-35-option-g' },
];

// Enforce max-3 on change (safety guard in addition to :disabled)
function handleQ33_35Change() {
    if (multipleAnswers.value.q33_35.length > 3) {
        multipleAnswers.value.q33_35 =
            multipleAnswers.value.q33_35.slice(0, 3);
    }

    multipleAnswers.value.q33_35.sort();
}

// ── Q36-40 draggable language options ────────────────────────────────────────
// The panel always shows all 6 options; options can be reused across questions
// (same behaviour as the Part 2 Q20-23 drag-and-drop panel).
const languageOptions = [
    { value: 'A', label: 'Welsh' },
    { value: 'B', label: 'Maori' },
    { value: 'C', label: 'Faroese' },
    { value: 'D', label: 'Romansch' },
    { value: 'E', label: 'Ainu' },
    { value: 'F', label: 'Kauma' },
];

// Helper used in the template drop-zone display: {{ answers.q36 ? getLanguageLabel(answers.q36) : '' }}
function getLanguageLabel(value) {
    const found = languageOptions.find((o) => o.value === value);
    return found ? `${found.value}. ${found.label}` : value;
}

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

const getHighlightedSegment = (segmentId: string) => {
  // Find the segment by ID
  const segment = textSegments.value.find((s) => s.id === segmentId);
  if (!segment) return '';

  const segmentText = segment.text;
  const segmentOffset = segment.offset;
  const segmentPlainTextLength = getPlainTextLength(segmentText);
  const segmentEnd = segmentOffset + segmentPlainTextLength;

  // Check if any highlights overlap with this segment
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
    return {
        ...answers.value,
        q33_35: multipleAnswers.value.q33_35
    };
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

        // Helper function to get absolute offset using TreeWalker for multiline support
        const getAbsoluteOffset = (node: Node, offsetInNode: number): number | null => {
            let container = node;
            if (container.nodeType !== Node.ELEMENT_NODE) {
                container = container.parentNode as Node;
            }
            const segmentEl = (container as Element).closest('[data-segment-id]');

            if (!segmentEl) {
                return null;
            }

            const segmentIdAttr = segmentEl.getAttribute('data-segment-id') || '';
            const segmentId = /^\d+$/.test(segmentIdAttr) ? parseInt(segmentIdAttr, 10) : segmentIdAttr;
            const segment = textSegments.value.find((s) => s.id === segmentId);
            if (!segment) {
                return null;
            }

            // Use TreeWalker to traverse all text nodes within the segment
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

        // Handle reversed selection (selecting from right to left)
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

// const highlightedPassageText = computed(() => {
//     return applyHighlightsToText(passageText.value);
// });

const handlePassageTextSelect = (event: MouseEvent) => {
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showContextMenu.value = false;
            return;
        }

        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (rect && passageTextRef.value) {
            // Use fixed positioning relative to viewport
            // Position menu ABOVE the selection
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70; // Approximate tooltip height
            const menuY = rect.top - menuHeight - 8; // 8px gap above selection

            // Ensure menu stays within viewport
            const viewportWidth = window.innerWidth;
            const menuWidth = 140; // Smaller width for new design

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10), // Ensure it doesn't go above viewport
            };
            showContextMenu.value = true;

            if (selection) {
                const preSelectionRange = range!.cloneRange();
                const container = passageTextRef.value;
                preSelectionRange.selectNodeContents(container);
                preSelectionRange.setEnd(range!.startContainer, range!.startOffset);

                const plainTextBefore = preSelectionRange.toString();
                const startOffset = plainTextBefore.length;
                const endOffset = startOffset + selected.length;

                selectedText.value = selected;
                selectionRange.value = { start: startOffset, end: endOffset };
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

    const modalWidth = 320; // w-80 = 20rem = 320px
    const modalHeight = 180; // Approximate height of the modal
    const padding = 10; // Minimum distance from viewport edges

    // Get fresh position based on current selection
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

    // Viewport boundary checking
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Check horizontal bounds (modal is centered, so check half-width on each side)
    const halfWidth = modalWidth / 2;
    if (x - halfWidth < padding) {
        x = halfWidth + padding;
    } else if (x + halfWidth > viewportWidth - padding) {
        x = viewportWidth - halfWidth - padding;
    }

    // Check vertical bounds
    if (y + modalHeight > viewportHeight - padding) {
        // Not enough space below, try to position above the selection
        if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            y = rect.top - modalHeight - 10;
        }
        // If still off-screen at top, just use the bottom with scroll
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

// Close delete tooltip
const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

// Handle click on content area - check if clicking on a highlight mark
const handleContentClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    const highlightMark = target.closest('mark[data-highlight-id]') as HTMLElement;

    if (highlightMark) {
        // Clicked on a highlight - show delete tooltip
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
        // Clicked outside highlight - close tooltips
        if (showDeleteTooltip.value) {
            closeDeleteTooltip();
        }
        if (showContextMenu.value) {
            showContextMenu.value = false;
        }
    }
};

const isBookmarkVisible = (questionNumber: number): boolean => {
    return hoveredQuestion.value === questionNumber || isQuestionFlagged(questionNumber);
};

// Delete highlight - simple and direct
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        const idToDelete = highlightToDelete.value;
        // Close tooltip and clear state first
        showDeleteTooltip.value = false;
        highlightToDelete.value = null;
        // Then delete
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

                // Position tooltip ABOVE the noted text with arrow pointing DOWN
                const tooltipHeight = 50; // Approximate tooltip height
                let y = rect.top - tooltipHeight - 8; // 8px gap above text

                // If not enough space above, show below
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

    // Check if we're moving to the tooltip itself
    if (relatedTarget?.closest('.note-hover-tooltip')) {
        return;
    }

    // Check if leaving a note mark
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

const closeContextMenu = () => {
    showContextMenu.value = false;
};

const handleDeleteHighlight = (id: number) => {
    deleteHighlight(id);
};

const handleClickOutside = (event: MouseEvent) => {
    if (showContextMenu.value) {
        showContextMenu.value = false;
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
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mouseover', handleNoteMouseEnter);
    document.addEventListener('mouseout', handleNoteMouseLeave);

    // Add resize event listeners
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

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
        <!-- Part 3 Header -->
        <div class="border-b-0.5 part-header-box mx-5 mt-4 border-gray-400 bg-gray-100 px-4 py-2">
            <p class="text-sm font-bold text-gray-900 select-text" data-segment-id="part-header" v-html="getHighlightedSegment('part-header')"></p>
            <p class="text-sm text-gray-700 select-text" data-segment-id="part-instructions" v-html="getHighlightedSegment('part-instructions')"></p>
        </div>

        <div class="mx-auto px-3 sm:px-4 lg:px-6 py-1">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">
                <!-- Reading Passage -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <!-- Passage Title -->
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-900">
                            <span data-segment-id="passage-title" v-html="getHighlightedSegment('passage-title')"></span>
                        </h2>
                    </div>

                    <div class="space-y-6 px-4" :style="contentZoom">
                        <div ref="passageTextRef" class="space-y-6 text-base leading-relaxed select-text sm:text-base">
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

                <!-- Resize Handle -->
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

                <!-- Questions Section -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <!-- Scrollable Questions Content -->
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- Questions 28–32 (YES / NO / NOT GIVEN) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span
                                                class="text-gray-700 select-text"
                                                data-segment-id="q28-32-title"
                                                v-html="getHighlightedSegment('q28-32-title')"
                                            ></span>
                                        </h3>
                                    </div>
                                    <div class="border border-gray-300 p-6">
                                        <p class="text-base leading-relaxed font-medium text-gray-800 sm:text-base">
                                            <span data-segment-id="q28-32-instructions" v-html="getHighlightedSegment('q28-32-instructions')"></span>
                                            <strong data-segment-id="q28-32-passage-ref" v-html="getHighlightedSegment('q28-32-passage-ref')"></strong>
                                        </p>
                                        <p class="pt-2 text-base text-gray-700">
                                            <span data-segment-id="q28-32-in-boxes" v-html="getHighlightedSegment('q28-32-in-boxes')"></span>
                                            <strong data-segment-id="q28-32-range" v-html="getHighlightedSegment('q28-32-range')"></strong>
                                            <span data-segment-id="q28-32-write" v-html="getHighlightedSegment('q28-32-write')"></span>
                                        </p>
                                        <div class="grid grid-cols-1 gap-2 pt-4 text-base sm:text-base">
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-24 rounded bg-gray-100 px-2 py-1 font-bold text-gray-900"
                                                    data-segment-id="yes-label"
                                                    v-html="getHighlightedSegment('yes-label')"
                                                ></span>
                                                <span
                                                    class="text-gray-700 select-text"
                                                    data-segment-id="yes-desc"
                                                    v-html="getHighlightedSegment('yes-desc')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="w-24 rounded bg-gray-100 px-2 py-1 font-bold"
                                                    data-segment-id="no-label"
                                                    v-html="getHighlightedSegment('no-label')"
                                                ></span>
                                                <span
                                                    class="text-gray-700 select-text"
                                                    data-segment-id="no-desc"
                                                    v-html="getHighlightedSegment('no-desc')"
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
                                    <!-- Question 28 -->
                                    <div
                                        id="question-28"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q28-num" v-html="getHighlightedSegment('q28-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q28" v-html="getHighlightedSegment('q28')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q28" value="YES" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q28-yes" v-html="getHighlightedSegment('q28-yes')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q28" value="NO" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q28-no" v-html="getHighlightedSegment('q28-no')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q28" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q28-not-given" v-html="getHighlightedSegment('q28-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-show="isBookmarkVisible(28)"
                                            @click="emit('toggleFlag', 28)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 29 -->
                                    <div
                                        id="question-29"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q29-num" v-html="getHighlightedSegment('q29-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q29" v-html="getHighlightedSegment('q29')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q29" value="YES" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q29-yes" v-html="getHighlightedSegment('q29-yes')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q29" value="NO" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q29-no" v-html="getHighlightedSegment('q29-no')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q29" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q29-not-given" v-html="getHighlightedSegment('q29-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-show="isBookmarkVisible(29)"
                                            @click="emit('toggleFlag', 29)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 30 -->
                                    <div
                                        id="question-30"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q30-num" v-html="getHighlightedSegment('q30-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q30" v-html="getHighlightedSegment('q30')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q30" value="YES" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q30-yes" v-html="getHighlightedSegment('q30-yes')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q30" value="NO" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q30-no" v-html="getHighlightedSegment('q30-no')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q30" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q30-not-given" v-html="getHighlightedSegment('q30-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-show="isBookmarkVisible(30)"
                                            @click="emit('toggleFlag', 30)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 31 -->
                                    <div
                                        id="question-31"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q31-num" v-html="getHighlightedSegment('q31-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q31" v-html="getHighlightedSegment('q31')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q31" value="YES" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q31-yes" v-html="getHighlightedSegment('q31-yes')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q31" value="NO" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q31-no" v-html="getHighlightedSegment('q31-no')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q31" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q31-not-given" v-html="getHighlightedSegment('q31-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-show="isBookmarkVisible(31)"
                                            @click="emit('toggleFlag', 31)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Question 32 -->
                                    <div
                                        id="question-32"
                                        class="relative pr-10"
                                        @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="font-bold text-gray-900 select-text" data-segment-id="q32-num" v-html="getHighlightedSegment('q32-num')"></span>
                                            <div class="flex-1">
                                                <p class="text-base leading-relaxed text-gray-700 sm:text-base">
                                                    <span class="select-text" data-segment-id="q32" v-html="getHighlightedSegment('q32')"></span>
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-4">
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q32" value="YES" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q32-yes" v-html="getHighlightedSegment('q32-yes')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q32" value="NO" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q32-no" v-html="getHighlightedSegment('q32-no')"></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-2">
                                                        <input type="radio" v-model="answers.q32" value="NOT GIVEN" class="h-4 w-4 accent-black" />
                                                        <span class="select-text" data-segment-id="q32-not-given" v-html="getHighlightedSegment('q32-not-given')"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-show="isBookmarkVisible(32)"
                                            @click="emit('toggleFlag', 32)"
                                            class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 33-35 (Checkbox – max 3 selections) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q33-35-title" v-html="getHighlightedSegment('q33-35-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q33-35-instructions" v-html="getHighlightedSegment('q33-35-instructions')"></span><br />
                                        <span data-segment-id="q33-35-which" v-html="getHighlightedSegment('q33-35-which')"></span>
                                        <strong class="text-gray-900" data-segment-id="q33-35-three" v-html="getHighlightedSegment('q33-35-three')"></strong>
                                        <span data-segment-id="q33-35-factors-mentioned" v-html="getHighlightedSegment('q33-35-factors-mentioned')"></span><br />
                                        <span data-segment-id="q33-35-write" v-html="getHighlightedSegment('q33-35-write')"></span>
                                        <strong class="text-gray-900" data-segment-id="q33-35-letters" v-html="getHighlightedSegment('q33-35-letters')"></strong>
                                        <span data-segment-id="q33-35-in-boxes" v-html="getHighlightedSegment('q33-35-in-boxes')"></span>
                                    </p>
                                </div>

                                <div class="border border-gray-300 p-6">
                                    <h4 class="mb-3 text-sm font-bold text-gray-800" data-segment-id="q33-35-list-header" v-html="getHighlightedSegment('q33-35-list-header')"></h4>

                                    <!-- Selection counter hint -->
                                    <p class="mb-3 text-xs text-gray-500">
                                        Selected: {{ multipleAnswers.q33_35.length }} / 3
                                        <span v-if="multipleAnswers.q33_35.length >= 3" class="ml-1 text-gray-700 font-medium">(maximum reached)</span>
                                    </p>

                                    <div class="space-y-2 text-sm">
                                        <label
                                            v-for="option in q33_35Options"
                                            :key="option.value"
                                            class="flex cursor-pointer items-center gap-3 rounded border border-gray-200 px-3 py-2 transition-colors hover:bg-gray-50"
                                            :class="{
                                                'opacity-50 cursor-not-allowed': multipleAnswers.q33_35.length >= 3 && !multipleAnswers.q33_35.includes(option.value)
                                            }"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="option.value"
                                                v-model="multipleAnswers.q33_35"
                                                class="h-4 w-4 accent-black"
                                                :disabled="multipleAnswers.q33_35.length >= 3 && !multipleAnswers.q33_35.includes(option.value)"
                                                @change="handleQ33_35Change"
                                            />
                                            <span
                                                class="select-text text-gray-700"
                                                :data-segment-id="option.segmentId"
                                                v-html="getHighlightedSegment(option.segmentId)"
                                            ></span>
                                        </label>
                                    </div>

                                    <!-- Bookmark buttons for 33, 34, 35 -->
                                    <div class="mt-4 flex gap-3">
                                        <template v-for="qNum in [33, 34, 35]" :key="qNum">
                                            <span class="text-xs text-gray-500">Q{{ qNum }}:</span>
                                            <button
                                                v-show="isBookmarkVisible(qNum)"
                                                @click="emit('toggleFlag', qNum)"
                                                class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(qNum) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(qNum) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions 36-40 (Drag and Drop – match language to statement) -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <div class="mb-4 flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="q36-40-title" v-html="getHighlightedSegment('q36-40-title')"></span>
                                        </h3>
                                    </div>
                                    <p class="mb-4 text-sm leading-relaxed font-medium text-gray-800">
                                        <span data-segment-id="q36-40-instructions" v-html="getHighlightedSegment('q36-40-instructions')"></span><br />
                                        <span data-segment-id="q36-40-write" v-html="getHighlightedSegment('q36-40-write')"></span>
                                    </p>
                                </div>

                                <!-- Side by side layout: Questions (left) + Draggable language options (right) -->
                                <div class="flex gap-6">
                                    <!-- Left side: Questions with drop zones -->
                                    <div class="flex-1 border border-gray-900 p-6">
                                        <div class="space-y-4 text-sm leading-relaxed text-gray-800">

                                            <!-- Question 36 -->
                                            <div class="flex items-start gap-3">
                                                <span class="font-bold text-gray-900 select-text" data-segment-id="q36-num" v-html="getHighlightedSegment('q36-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q36" v-html="getHighlightedSegment('q36')"></span>
                                                </div>
                                                <span class="inline-flex items-center space-x-2" @mouseenter="hoveredQuestion = 36" @mouseleave="hoveredQuestion = null">
                                                    <span
                                                        id="question-36"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                        :class="dragOverQuestion === 36 ? 'border-gray-600 bg-gray-50' : answers.q36 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 36)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 36)"
                                                        @click="clearAnswer(36)"
                                                        :title="answers.q36 ? 'Click to clear' : 'Drop answer here'"
                                                    >
                                                        {{ answers.q36 ? getLanguageLabel(answers.q36) : '' }}
                                                    </span>
                                                    <button
                                                        @click.stop="emit('toggleFlag', 36)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(36) ? 'border-red-400 text-red-500' : 'border-gray-100 text-gray-100 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                            <!-- Question 37 -->
                                            <div class="flex items-start gap-3">
                                                <span class="font-bold text-gray-900 select-text" data-segment-id="q37-num" v-html="getHighlightedSegment('q37-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q37" v-html="getHighlightedSegment('q37')"></span>
                                                </div>
                                                <span class="inline-flex items-center space-x-2" @mouseenter="hoveredQuestion = 37" @mouseleave="hoveredQuestion = null">
                                                    <span
                                                        id="question-37"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                        :class="dragOverQuestion === 37 ? 'border-gray-600 bg-gray-50' : answers.q37 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 37)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 37)"
                                                        @click="clearAnswer(37)"
                                                        :title="answers.q37 ? 'Click to clear' : 'Drop answer here'"
                                                    >
                                                        {{ answers.q37 ? getLanguageLabel(answers.q37) : '' }}
                                                    </span>
                                                    <button
                                                        @click.stop="emit('toggleFlag', 37)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(37) ? 'border-red-400 text-red-500' : 'border-gray-100 text-gray-100 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                            <!-- Question 38 -->
                                            <div class="flex items-start gap-3">
                                                <span class="font-bold text-gray-900 select-text" data-segment-id="q38-num" v-html="getHighlightedSegment('q38-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q38" v-html="getHighlightedSegment('q38')"></span>
                                                </div>
                                                <span class="inline-flex items-center space-x-2" @mouseenter="hoveredQuestion = 38" @mouseleave="hoveredQuestion = null">
                                                    <span
                                                        id="question-38"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                        :class="dragOverQuestion === 38 ? 'border-gray-600 bg-gray-50' : answers.q38 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 38)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 38)"
                                                        @click="clearAnswer(38)"
                                                        :title="answers.q38 ? 'Click to clear' : 'Drop answer here'"
                                                    >
                                                        {{ answers.q38 ? getLanguageLabel(answers.q38) : '' }}
                                                    </span>
                                                    <button
                                                        @click.stop="emit('toggleFlag', 38)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(38) ? 'border-red-400 text-red-500' : 'border-gray-100 text-gray-100 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                            <!-- Question 39 -->
                                            <div class="flex items-start gap-3">
                                                <span class="font-bold text-gray-900 select-text" data-segment-id="q39-num" v-html="getHighlightedSegment('q39-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q39" v-html="getHighlightedSegment('q39')"></span>
                                                </div>
                                                <span class="inline-flex items-center space-x-2" @mouseenter="hoveredQuestion = 39" @mouseleave="hoveredQuestion = null">
                                                    <span
                                                        id="question-39"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                        :class="dragOverQuestion === 39 ? 'border-gray-600 bg-gray-50' : answers.q39 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 39)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 39)"
                                                        @click="clearAnswer(39)"
                                                        :title="answers.q39 ? 'Click to clear' : 'Drop answer here'"
                                                    >
                                                        {{ answers.q39 ? getLanguageLabel(answers.q39) : '' }}
                                                    </span>
                                                    <button
                                                        @click.stop="emit('toggleFlag', 39)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(39) ? 'border-red-400 text-red-500' : 'border-gray-100 text-gray-100 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                            <!-- Question 40 -->
                                            <div class="flex items-start gap-3">
                                                <span class="font-bold text-gray-900 select-text" data-segment-id="q40-num" v-html="getHighlightedSegment('q40-num')"></span>
                                                <div class="flex-1">
                                                    <span class="select-text" data-segment-id="q40" v-html="getHighlightedSegment('q40')"></span>
                                                </div>
                                                <span class="inline-flex items-center space-x-2" @mouseenter="hoveredQuestion = 40" @mouseleave="hoveredQuestion = null">
                                                    <span
                                                        id="question-40"
                                                        class="inline-flex min-h-8 min-w-28 items-center justify-center rounded border-2 border-dashed px-3 py-1 text-center text-sm transition-all cursor-pointer"
                                                        :class="dragOverQuestion === 40 ? 'border-gray-600 bg-gray-50' : answers.q40 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                                                        @dragover="handleDragOver($event, 40)"
                                                        @dragleave="handleDragLeave"
                                                        @drop="handleDrop($event, 40)"
                                                        @click="clearAnswer(40)"
                                                        :title="answers.q40 ? 'Click to clear' : 'Drop answer here'"
                                                    >
                                                        {{ answers.q40 ? getLanguageLabel(answers.q40) : '' }}
                                                    </span>
                                                    <button
                                                        @click.stop="emit('toggleFlag', 40)"
                                                        class="flex h-6 w-6 items-center justify-center rounded border transition-all"
                                                        :class="isQuestionFlagged(40) ? 'border-red-400 text-red-500' : 'border-gray-100 text-gray-100 hover:border-gray-400 hover:text-gray-500'"
                                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                                    >
                                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Right side: Draggable language options (always available, never removed) -->
                                    <div class="w-48 shrink-0 self-start sticky top-12">
                                        <p class="mb-2 text-sm font-medium">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 p-2 bg-white">
                                            <div class="space-y-1 text-sm">
                                                <div
                                                    v-for="option in languageOptions"
                                                    :key="option.value"
                                                    class="flex cursor-grab items-center space-x-1.5 rounded border border-gray-300 px-2 py-1 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="font-bold text-gray-900 text-xs">{{ option.value }}</span>
                                                    <span class="text-gray-700 text-xs">{{ option.label }}</span>
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
                            <svg
                                class="h-5 w-5 text-gray-900"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                            >
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
                            <svg
                                class="h-5 w-5 text-gray-700"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
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
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                ></path>
                            </svg>
                        </span>
                        <span class="note-tooltip-text max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button
                            @click="deleteNoteFromTooltip"
                            class="note-delete-btn shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note"
                        >
                            <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                ></path>
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
                    <button
                        @click="cancelNote"
                        class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                    >
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
