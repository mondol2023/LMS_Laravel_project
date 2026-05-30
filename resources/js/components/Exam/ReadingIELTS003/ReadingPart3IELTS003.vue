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


// Hover state for showing bookmark buttons
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// Reading Part 3 component

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

// Drag and drop functionality for questions 28-33
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

const placeOptions = [
    { value: 'A', label: 'other rainforests may have originally been planted by man.' },
    { value: 'B', label: "many of the island's original species were threatened with destruction." },
    { value: 'C', label: 'the species in the original rainforest were more successful than the newer arrivals.' },
    { value: 'D', label: 'rainforests can only develop through a process of slow and complex evolution.' },
    { value: 'E', label: 'steps should be taken to prevent the destruction of the original ecosystem.' },
    { value: 'F', label: 'randomly introduced species can coexist together.' },
    { value: 'G', label: 'the introduced species may have less ecological significance than the original ones.' },
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

const getOptionLabel = (value: string): string => {
    const option = placeOptions.find((opt) => opt.value === value);
    return option ? `${option.value}. ${option.label}` : '';
};

// Only show options not yet used (each option usable once)
const availableOptions = computed(() => {
    const usedOptions = [
        answers.value.q33,
        answers.value.q34,
        answers.value.q35,
        answers.value.q36,
        answers.value.q37,
    ].filter(Boolean);
    return placeOptions.filter((opt) => !usedOptions.includes(opt.value));
});

// Paragraph texts for A-F (split from passage)
const passageText = `When Peter Osbeck. a Swedish priest, stopped off at the mid-Atlantic island of Ascension in 1752 on his way home from China, he wrote of ‘a heap of ruinous rocks’ with a bare, white mountain in the middle. All it boasted was a couple of dozen species of plant, most of them ferns and some of them unique to the island.

And so it might have remained. But in 1843 British plant collector Joseph Hooker made a brief call on his return from Antarctica.Surveying the bare earth, he concluded that the island had suffered some natural calamity that had denuded it of vegetation and triggered a decline in rainfall that was turning the place into a desert. The British Navy, which by then maintained a garrison on the island, was keen to improve the place and asked Hooker's advice. He suggested an ambitious scheme for planting trees and shrubs that would revive rainfall and stimulate a wider ecological recovery. And, perhaps lacking anything else to do, the sailors set to with a will.

In 1845, a naval transport ship from Argentina delivered a batch of seedlings. In the following years, more than 200 species of plant arrived from South Africa, from England came 700 packets of seeds, including those of two species that especially lik ed the place: bamboo and prickly pear. With sailors planting several thousand trees a year, the bare white mountain was soon cloaked in green and renamed Green Mountain, and by the early twentieth century, the mountain's slopes were covered with a variety of trees and shrubs from all over the world.

Modern ecologists throw up their hands in horror at what they see as Hookers environmental anarchy. The exotic species wrecke d the indigenous ecosystem, squeezing out the islands endemic plants. In fact. Hooker knew well enough what might happen. However, he saw greater benefit in improving rainfall and encouraging more prolific vegetation on the island.

But there is a much deeper issue here than the relative benefits of sparse endemic species versus luxuriant imported ones. And as botanist David Wilkinson of Liverpool John Moores University in the UK pointed out after a recent visit to the island, it goes to the heart of some of the most dearly held tenets of ecology. Conservationists' understandable concern for the fate of Ascension’s handful of unique species has, he says, blinded them to something quite astonishing the fact that the introduced species have been a roaring success.

Today's Green Mountain, says Wilkinson, is ‘a fully functioning man-made tropical cloud forest' that has grown from scratch from a ragbag of species collected more or less at random from all over the planet. But how could it have happened? Conventional eco logical theory says that complex ecosystems such as cloud forests can emerge only through evolutionary processes in which each organism develops in concert with others to fill particular niches. Plants eco-evolve with their pollinators and seed dispersers, while microbes in the soil evolve to deal with the leaf litter.

But that’s not what happened on Green Mountain. And the experience suggests that perhaps natural rainforests are constructed far more by chance than by evolution. Species, say some ecologists, don’t so much evolve to create ecosystems as make the best of what th ey have. ‘The Green Mountain system is an example of how we can create a complex ecosystem by chance, and not by evolution, says Wilkinson.

Not everyone agrees. Alan Gray, an ecologist at the University of Edinburgh in the UK. arg ues that the surviving endemic species on Green Mountain, though small in number, may still form the framework of the new' ecosystem. The new arrivals may just be an adornment, with little structural importance for the ecosystem.

But to Wilkinson, this sounds like clutching at straws. And the idea of the instant formation of rainforests sounds increasingly plausible as research reveals that supposedly pristine tropical rainforests from the Amazon to south -east Asia may in places be little more titan the overgrown gardens of past rainforest civilisations.

The most surprising thing of all is that no ecologists have thought to conduct proper research into this human -made rainforest ecosystem. A survey of the island’s flora conducted six years ago by the University of Edinburgh was concerned only with endemic species. They characterised everything else as a threat. And the Ascension authorities are currently turning Green Mountain into a national park where introduced species, at least the invasive ones, are earmarked for culling rather than conservation.

Conservationists have understandable concerns, Wilkinson says. At least four endemic species have gone extinct on Ascension since the exotics started arriving. But in their urgency to protect endemics, ecologists are missing out on the study of a great enigma.’

‘As you walk through the forest, you see lots of leaves that have had chunks taken out of them by various insects. There are caterpillars and beetles around.' says Wilkinson. ‘But where did they come from? Are they endemic or alien? If alien, did they come with the plant on which they feed or discover it on arrival?’ Such questions go to the heart of how- rainforests happen.

The Green Mountain forest holds many secrets. And the irony is that the most artificial rainforest in the world could tell us more about
rainforest ecology than any number of natural forests.

`;
const textSegments = computed(() =>{
    // ── Part header ──────────────────────────── cumulative from 0
    const rawSegments = [
        { id: 'part3-header',        text: 'Part 3',                                                                                                                                                             },
    // "Part 3" = 6 chars → next: 6
    { id: 'part3-instruction',   text: 'Read the text and answer questions 28-40.',                                                                                                                          },
    // 41 chars → next: 47
    { id: 'title',               text: 'The accidental rainforest\nAccording to ecological theory, rainforests are supposed to develop slowly over millions of years. But now ecologists are being forced to reconsider their ideas',   },
    // 186 chars → next would be 233, but passage starts at 5000 (user-specified)

    // ── Passage ──────────────────────────────── user-specified anchor
    { id: 'passage',             text: passageText,                                                                                                                                                         },
    // passageText.length is dynamic; post-passage segments anchor at 5389 (passageText.length = 389)

    // ── Questions 27-32 ──────────────────────── cumulative from 5389
    { id: 'q27-32-title',        text: 'Questions 27-32',                                                                                                                                                   },
    // 15 chars → next: 5404
    { id: 'q27-32-instructions', text: 'Do the following statements agree with the information given in Reading Passage 3? In boxes 27-32 on your answer sheet write-',                                        },
    // 125 chars → next: 5529
    { id: 'q27-32-true',         text: 'if the statement agrees with the information',                                                                                                                       },
    // 44 chars → next: 5573
    { id: 'q27-32-false',        text: 'if the statement contradicts the information',                                                                                                                      },
    // 44 chars → next: 5617
    { id: 'q27-32-notgiven',     text: 'if there is no information on this',                                                                                                                               },
    // 34 chars → next: 5651
    { id: 'q27-text',            text: '27. When Peter Osbeck visited Ascension, he found no inhabitants on the island.',                                                                                       },
    // 75 chars → next: 5726
    { id: 'q28-text',            text: '28. The natural vegetation on the island contained some species which were found nowhere else.',                                                                          },
    // 90 chars → next: 5816
    { id: 'q29-text',            text: "29. Joseph Hooker assumed that human activity had caused the decline in the island's plant life.",                                                                        },
    // 92 chars → next: 5908
    { id: 'q30-text',            text: '30. British sailors on the island took part in a major tree planting project.',                                                                                           },
    // 73 chars → next: 5981
    { id: 'q31-text',            text: '31. Hooker sent details of his planting scheme to a number of different countries.',                                                                                      },
    // 78 chars → next: 6059
    { id: 'q32-text',            text: '32. The bamboo and prickly pear seeds sent from England were unsuitable for Ascension.',                                                                                 },
    // 82 chars → next: 6141

    // ── Questions 33-37 ──────────────────────── cumulative from 6141
    { id: 'q33-37-title',        text: 'Questions 33-37',                                                                                                                                                  },
    // 15 chars → next: 6156
    { id: 'q33-37-instructions', text: 'Complete each sentence with the correct ending A–G from the box below. Write the correct letter A–G in boxes 33–37 on your answer sheet.',                            },
    // 136 chars → next: 6292
    { id: 'option-a',            text: 'other rainforests may have originally been planted by man.',                                                                                                        },
    // 58 chars → next: 6350
    { id: 'option-b',            text: "many of the island's original species were threatened with destruction.",                                                                                            },
    // 71 chars → next: 6421
    { id: 'option-c',            text: 'the species in the original rainforest were more successful than the newer arrivals.',                                                                                },
    // 84 chars → next: 6505
    { id: 'option-d',            text: 'rainforests can only develop through a process of slow and complex evolution.',                                                                                      },
    // 77 chars → next: 6582
    { id: 'option-e',            text: 'steps should be taken to prevent the destruction of the original ecosystem.',                                                                                       },
    // 75 chars → next: 6657
    { id: 'option-f',            text: 'randomly introduced species can coexist together.',                                                                                                                  },
    // 49 chars → next: 6706
    { id: 'option-g',            text: 'the introduced species may have less ecological significance than the original ones.',                                                                                },
    // 84 chars → next: 6790
    { id: 'q33-text',            text: "33. The reason for modern conservationists' concern over Hooker's tree planting programme is that",                                                                        },
    // 93 chars → next: 6883
    { id: 'q34-text',            text: '34. David Wilkinson says the creation of the rainforest in Ascension is important because it shows that',                                                                 },
    // 99 chars → next: 6982
    { id: 'q35-text',            text: '35. Wilkinson says the existence of Ascension\u2019s rainforest challenges the theory that',                                                                              },
    // 81 chars → next: 7063
    { id: 'q36-text',            text: '36. Alan Gray questions Wilkinson\u2019s theory, claiming that',                                                                                                         },
    // 53 chars → next: 7116
    { id: 'q37-text',            text: '37. Additional support for Wilkinson\'s theory comes from findings that',                                                                                                  },
    // 66 chars → next: 7182

    // ── Questions 38-40 ──────────────────────── cumulative from 7182
    { id: 'q38-40-title',        text: 'Questions 38-40',                                                                                                                                                   },
    // 15 chars → next: 7197
    { id: 'q38-40-instructions', text: 'Choose the correct letter, A, B, C or D. Write your answers in boxes 38-40 on your answer sheet.',                                                                   },
    // 96 chars → next: 7293
    { id: 'q38-question',        text: "38. Wilkinson suggests that conservationists' concern about the island is misguided because",                                                                              },
    // 87 chars → next: 7380
    { id: 'q38-a',               text: 'it is based on economic rather than environmental principles.',                                                                                                      },
    // 61 chars → next: 7441
    { id: 'q38-b',               text: 'it is not focusing on the most important question.',                                                                                                                 },
    // 50 chars → next: 7491
    { id: 'q38-c',               text: 'it is encouraging the destruction of endemic species.',                                                                                                              },
    // 53 chars → next: 7544
    { id: 'q38-d',               text: 'it is not supported by the local authorities.',                                                                                                                     },
    // 45 chars → next: 7589
    { id: 'q39-question',        text: '39. According to Wilkinson, studies of insects on the island could demonstrate',                                                                                        },
    // 74 chars → next: 7663
    { id: 'q39-a',               text: 'the possibility of new ecological relationships.',                                                                                                                   },
    // 48 chars → next: 7711
    { id: 'q39-b',               text: 'a future threat to the ecosystem of the island.',},
    { id: 'q39-c',               text: 'the existence of previously unknown species.',                                          },
    // 44 chars → next: 7802
    { id: 'q39-d',               text: 'a chance for the survival of rainforest ecology.',                                                                                                                   },
    // 48 chars → next: 7850
    { id: 'q40-question',        text: '40. Overall, what feature of the Ascension rainforest does the writer stress?',                                                                                          },
    // 73 chars → next: 7923
    { id: 'q40-a',               text: 'the conflict of natural and artificial systems',                                       },
    // 46 chars → next: 7969
    { id: 'q40-b',               text: 'the unusual nature of its ecological structure',                                                                                                                     },
    // 46 chars → next: 8015
    { id: 'q40-c',               text: 'the harm done by interfering with nature',                                                                                                                           },
    // 40 chars → next: 8055
    { id: 'q40-d',               text: 'the speed and success of its development'    },
    // 40 chars → end: 8095
    ];

    let currentOffset = 0;
    return rawSegments.map(seg => {
        const result = { ...seg, offset: currentOffset };
        currentOffset += getPlainTextLength(seg.text); // use plain text length, not raw string length
        return result;
    });
});
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
            // Handle both string and number segment IDs
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
            // Position tooltip ABOVE the selection with arrow pointing down
            const menuX = rect.left + rect.width / 2;
            const menuHeight = 70; // Approximate tooltip height
            const menuY = rect.top - menuHeight - 8; // 8px gap above selection

            const viewportWidth = window.innerWidth;
            const menuWidth = 140; // Smaller width for new design

            contextMenuPosition.value = {
                x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
                y: Math.max(menuY, 10), // Ensure it doesn't go above viewport
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

    // Position note input below the context menu
    noteInputPosition.value = {
        x: contextMenuPosition.value.x,
        y: contextMenuPosition.value.y + 60,
    };

    showNoteInput.value = true;
    showContextMenu.value = false;

    // Focus the input after a small delay
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
        part: 'Part 3',
    });

    // Clear and close
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

// Handle note hover to show tooltip
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

// Confirm delete highlight
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

// Cancel delete highlight
const cancelDeleteHighlight = () => {
    closeDeleteTooltip();
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

const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    highlightToDelete.value = null;
};

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
                y: rect.bottom + 8, // Position below the highlight
            };
            highlightToDelete.value = parseInt(highlightId, 10);
            showDeleteTooltip.value = true;
        }
    } else {
        // Clicked elsewhere - close delete tooltip
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

    // Set minimum and maximum widths (20% to 80%)
    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
    }
};

const stopResize = () => {
    isResizing.value = false;
};

// Save panel width to localStorage
// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
    localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});

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

        <!-- Part 3 Header -->
        <div class="part-header-box mb-3 ml-1 rounded border border-gray-200 px-4 py-3">
            <p
                class="text-base font-bold text-gray-900 select-text"
                data-segment-id="part3-header"
                v-html="getHighlightedSegment('Part 3')"
            ></p>
            <p
                class="text-sm text-gray-700 select-text"
                data-segment-id="part3-instruction"
                v-html="getHighlightedSegment('Read the text and answer questions 28-40.')"
            ></p>
        </div>

        <div class="mx-auto px-3 py-0.5 sm:px-4 lg:px-6">
            <div ref="containerRef" class="relative flex flex-col gap-0 lg:flex-row" :class="{ 'select-none': isResizing }">

                <!-- ── Reading Passage Panel ── -->
                <div class="passage-panel mb-4 max-h-screen overflow-y-auto lg:mb-0" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="pt-6">
                        <h2 class="px-4 text-center text-xl font-bold text-gray-900">
                            <span
                                class="text-gray-700 select-text"
                                data-segment-id="title"
                                v-html="getHighlightedSegment('The accidental rainforest\nAccording to ecological theory, rainforests are supposed to develop slowly over millions of years. But now ecologists are being forced to reconsider their ideas')"
                            ></span>
                        </h2>
                    </div>
                    <div class="flex-1 space-y-6 overflow-y-auto px-4" :style="contentZoom">
                        <div class="space-y-6 text-base leading-relaxed select-text">
                            <div class="p-4">

                                <div class="text-justify text-[16px] leading-relaxed whitespace-pre-wrap text-gray-700">
                                    <span
                                        class="text-gray-700 select-text"
                                        data-segment-id="passage"
                                        v-html="getHighlightedSegment(passageText)"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" transform="rotate(90 12 12)" />
                        </svg>
                    </div>
                </div>

                <!-- ── Questions Panel ── -->
                <div class="answer-panel flex max-h-screen flex-col overflow-y-auto" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                    <div class="flex-1 overflow-y-auto pb-32">
                        <div class="space-y-8 p-4" :style="contentZoom">

                            <!-- ════════════════════════════════════
                                 Questions 27-32  (TRUE/FALSE/NOT GIVEN)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-base font-bold text-gray-900">
                                        <span
                                            data-segment-id="q27-32-title"
                                            v-html="getHighlightedSegment('Questions 27-32')"
                                        ></span>
                                    </h3>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span
                                            data-segment-id="q27-32-instructions"
                                            v-html="getHighlightedSegment('Do the following statements agree with the information given in Reading Passage 3? In boxes 27-32 on your answer sheet write-')"
                                        ></span>
                                    </p>
                                    <div class="mt-4  bg-white p-4">
                                        <h4 class="mb-3 text-base font-bold text-gray-900">List of headings:</h4>
                                        <div class="grid grid-cols-1 gap-2 pt-4 text-base">
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded  px-2 py-1 font-bold text-grey-700">TRUE</span>
                                                <span
                                                    data-segment-id="q27-32-true"
                                                    v-html="getHighlightedSegment('if the statement agrees with the information')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded  px-2 py-1 font-bold text-grey-700">FALSE</span>
                                                <span
                                                    data-segment-id="q27-32-false"
                                                    v-html="getHighlightedSegment('if the statement contradicts the information')"
                                                ></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="w-24 rounded  px-2 py-1 font-bold text-gray-700">NOT GIVEN</span>
                                                <span
                                                    data-segment-id="q27-32-notgiven"
                                                    v-html="getHighlightedSegment('if there is no information on this')"
                                                ></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Q27 -->
                                    <div
                                        id="question-27"
                                        class="relative  bg-white p-4"
                                        @mouseenter="hoveredQuestion = 27"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex flex-col gap-4">


                                                <div class="w-full">
                                                    <p class="text-base leading-relaxed text-gray-700">
                                                        <span
                                                            data-segment-id="q27-text"
                                                            v-html="getHighlightedSegment('27. When Peter Osbeck visited Ascension, he found no inhabitants on the island.')"
                                                        ></span>
                                                    </p>
                                                </div>
                                                <div class="w-full sm:w-48">
                                                    <label
                                                        v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                        :key="option"
                                                        class="flex items-center gap-2 cursor-pointer group"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="q27"
                                                            :value="option"
                                                            v-model="answers.q27"
                                                            class="h-4 w-4 accent-black cursor-pointer"
                                                        />
                                                        <span
                                                            class="text-base text-gray-700 group-hover:text-gray-900 transition-colors"
                                                            :class="{ 'font-semibold text-gray-900': answers.q27 === option }"
                                                        >
                                                            {{ option }}
                                                        </span>
                                                    </label>
                                                </div>


                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                            @click.stop="toggleFlag(27)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q28 -->
                                    <div
                                        id="question-28"
                                        class="relative  bg-white p-4"
                                        @mouseenter="hoveredQuestion = 28"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                    <div class="col-span-9">
                                                    <p class="text-base leading-relaxed text-gray-700">
                                                        <span
                                                            data-segment-id="q28-text"
                                                            v-html="getHighlightedSegment('28. The natural vegetation on the island contained some species which were found nowhere else.')"
                                                        ></span>
                                                    </p>
                                                </div>
                                        <div class="flex items-start gap-4">

                                            <div class="grid grid-cols-12 gap-2">
                                                <div class="col-span-3">
                                                    <label
                                                        v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                        :key="option"
                                                        class="flex items-center gap-2 cursor-pointer group"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="q28"
                                                            :value="option"
                                                            v-model="answers.q28"
                                                            class="h-4 w-4 accent-black cursor-pointer"
                                                        />
                                                        <span
                                                            class="text-base text-gray-700 group-hover:text-gray-900 transition-colors"
                                                            :class="{ 'font-semibold text-gray-900': answers.q28 === option }"
                                                        >
                                                            {{ option }}
                                                        </span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                            @click.stop="toggleFlag(28)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q29 -->
                                    <div
                                        id="question-29"
                                        class="relative  bg-white p-4"
                                        @mouseenter="hoveredQuestion = 29"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex flex-col gap-4">

                                            <div class="grid grid-cols-12 gap-2">
                                                 <div class="col-span-12">
                                                    <p class="text-base leading-relaxed text-gray-700">
                                                        <span
                                                            data-segment-id="q29-text"
                                                            v-html="getHighlightedSegment(`29. Joseph Hooker assumed that human activity had caused the decline in the island's plant life.`)"
                                                        ></span>
                                                    </p>
                                                </div>
                                                <div class="col-span-3">
                                                    <label
                                                        v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                        :key="option"
                                                        class="flex items-center gap-2 cursor-pointer group"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="q29"
                                                            :value="option"
                                                            v-model="answers.q29"
                                                            class="h-4 w-4 accent-black cursor-pointer"
                                                        />
                                                        <span
                                                            class="text-base text-gray-700 group-hover:text-gray-900 transition-colors"
                                                            :class="{ 'font-semibold text-gray-900': answers.q29 === option }"
                                                        >
                                                            {{ option }}
                                                        </span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                            @click.stop="toggleFlag(29)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q30 -->
                                    <div
                                        id="question-30"
                                        class="relative  bg-white p-4"
                                        @mouseenter="hoveredQuestion = 30"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex flex-col gap-4">

                                            <div class="grid grid-cols-12 gap-2">
                                                <div class="col-span-12">
                                                    <p class="text-base leading-relaxed text-gray-700">
                                                        <span
                                                            data-segment-id="q30-text"
                                                            v-html="getHighlightedSegment('30. British sailors on the island took part in a major tree planting project.')"
                                                        ></span>
                                                    </p>
                                                </div>
                                                <div class="col-span-3">
                                                    <label
                                                        v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                        :key="option"
                                                        class="flex items-center gap-2 cursor-pointer group"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="q30"
                                                            :value="option"
                                                            v-model="answers.q30"
                                                            class="h-4 w-4 accent-black cursor-pointer"
                                                        />
                                                        <span
                                                            class="text-base text-gray-700 group-hover:text-gray-900 transition-colors"
                                                            :class="{ 'font-semibold text-gray-900': answers.q30 === option }"
                                                        >
                                                            {{ option }}
                                                        </span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                            @click.stop="toggleFlag(30)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q31 -->
                                    <div
                                        id="question-31"
                                        class="relative  bg-white p-4"
                                        @mouseenter="hoveredQuestion = 31"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex flex-col gap-4">

                                            <div class="grid grid-cols-12 gap-2">
                                                <div class="col-span-12">
                                                    <p class="text-base leading-relaxed text-gray-700">
                                                        <span
                                                            data-segment-id="q31-text"
                                                            v-html="getHighlightedSegment('31. Hooker sent details of his planting scheme to a number of different countries.')"
                                                        ></span>
                                                    </p>
                                                </div>
                                                <div class="col-span-3">
                                                    <label
                                                        v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                        :key="option"
                                                        class="flex items-center gap-2 cursor-pointer group"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="q31"
                                                            :value="option"
                                                            v-model="answers.q31"
                                                            class="h-4 w-4 accent-black cursor-pointer"
                                                        />
                                                        <span
                                                            class="text-base text-gray-700 group-hover:text-gray-900 transition-colors"
                                                            :class="{ 'font-semibold text-gray-900': answers.q31 === option }"
                                                        >
                                                            {{ option }}
                                                        </span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                            @click.stop="toggleFlag(31)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(31) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Q32 -->
                                    <div
                                        id="question-32"
                                        class="relative  bg-white p-4"
                                        @mouseenter="hoveredQuestion = 32"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="flex flex-col gap-4">

                                            <div class="grid grid-cols-12 gap-2">
                                                <div class="col-span-12">
                                                    <p class="text-base leading-relaxed text-gray-700">
                                                        <span
                                                            data-segment-id="q32-text"
                                                            v-html="getHighlightedSegment('32. The bamboo and prickly pear seeds sent from England were unsuitable for Ascension.')"
                                                        ></span>
                                                    </p>
                                                </div>
                                                <div class="col-span-3">
                                                    <label
                                                        v-for="option in ['TRUE', 'FALSE', 'NOT GIVEN']"
                                                        :key="option"
                                                        class="flex items-center gap-2 cursor-pointer group"
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="q32"
                                                            :value="option"
                                                            v-model="answers.q32"
                                                            class="h-4 w-4 accent-black cursor-pointer"
                                                        />
                                                        <span
                                                            class="text-base text-gray-700 group-hover:text-gray-900 transition-colors"
                                                            :class="{ 'font-semibold text-gray-900': answers.q32 === option }"
                                                        >
                                                            {{ option }}
                                                        </span>
                                                    </label>
                                                </div>

                                            </div>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                            @click.stop="toggleFlag(32)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(32) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 33-37  (Sentence endings A–G)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-base font-bold text-gray-900">
                                        <span
                                            data-segment-id="q33-37-title"
                                            v-html="getHighlightedSegment('Questions 33-37')"
                                        ></span>
                                    </h3>
                                    <p class="mb-4 text-base leading-relaxed text-gray-700">
                                        <span
                                            data-segment-id="q33-37-instructions"
                                            v-html="getHighlightedSegment('Complete each sentence with the correct ending A–G from the box below. Write the correct letter A–G in boxes 33–37 on your answer sheet.')"
                                        ></span>
                                    </p>
                                </div>

                                <!-- Drag-and-Drop Layout: Questions left, Options right -->
                                <div class="flex gap-10">

                                    <!-- Left: Questions with drop zones -->
                                    <div class="flex-1 space-y-5">

                                        <!-- Q33 -->
                                        <div
                                            id="question-33"
                                            class="group relative"
                                            @mouseenter="hoveredQuestion = 33"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <p class="mb-2 text-base leading-relaxed text-gray-800 select-text">
                                                <span data-segment-id="q33-text" v-html="getHighlightedSegment(`33. The reason for modern conservationists' concern over Hooker's tree planting programme is that`)"></span>
                                            </p>
                                            <span
                                                class="inline-flex min-h-10 min-w-64 items-center justify-center rounded border-2 border-dashed px-3 py-2 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 33 ? 'border-blue-500 bg-blue-50' : answers.q33 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 33)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 33)"
                                                @click="clearAnswer(33)"
                                                :title="answers.q33 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                <span v-if="answers.q33">{{ getOptionLabel(answers.q33) }}</span>
                                                <span v-else class="font-bold text-gray-400">33</span>
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                                @click.stop="toggleFlag(33)"
                                                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(33) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </div>

                                        <!-- Q34 -->
                                        <div
                                            id="question-34"
                                            class="group relative"
                                            @mouseenter="hoveredQuestion = 34"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <p class="mb-2 text-base leading-relaxed text-gray-800 select-text">
                                                <span data-segment-id="q34-text" v-html="getHighlightedSegment('34. David Wilkinson says the creation of the rainforest in Ascension is important because it shows that')"></span>
                                            </p>
                                            <span
                                                class="inline-flex min-h-10 min-w-64 items-center justify-center rounded border-2 border-dashed px-3 py-2 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 34 ? 'border-blue-500 bg-blue-50' : answers.q34 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 34)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 34)"
                                                @click="clearAnswer(34)"
                                                :title="answers.q34 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                <span v-if="answers.q34">{{ getOptionLabel(answers.q34) }}</span>
                                                <span v-else class="font-bold text-gray-400">34</span>
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                                @click.stop="toggleFlag(34)"
                                                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(34) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </div>

                                        <!-- Q35 -->
                                        <div
                                            id="question-35"
                                            class="group relative"
                                            @mouseenter="hoveredQuestion = 35"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <p class="mb-2 text-base leading-relaxed mr-2 text-gray-800 select-text">
                                                <span data-segment-id="q35-text" v-html="getHighlightedSegment('35. Wilkinson says the existence of Ascension\u2019s rainforest challenges the theory that')"></span>
                                            </p>
                                            <span
                                                class="inline-flex min-h-10 min-w-64 items-center justify-center rounded border-2 border-dashed px-3 py-2 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 35 ? 'border-blue-500 bg-blue-50' : answers.q35 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 35)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 35)"
                                                @click="clearAnswer(35)"
                                                :title="answers.q35 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                <span v-if="answers.q35">{{ getOptionLabel(answers.q35) }}</span>
                                                <span v-else class="font-bold text-gray-400">35</span>
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                                @click.stop="toggleFlag(35)"
                                                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(35) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </div>

                                        <!-- Q36 -->
                                        <div
                                            id="question-36"
                                            class="group relative"
                                            @mouseenter="hoveredQuestion = 36"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <p class="mb-2 text-base leading-relaxed text-gray-800 select-text">
                                                <span data-segment-id="q36-text" v-html="getHighlightedSegment('36. Alan Gray questions Wilkinson\u2019s theory, claiming that')"></span>
                                            </p>
                                            <span
                                                class="inline-flex min-h-10 min-w-64 items-center justify-center rounded border-2 border-dashed px-3 py-2 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 36 ? 'border-blue-500 bg-blue-50' : answers.q36 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 36)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 36)"
                                                @click="clearAnswer(36)"
                                                :title="answers.q36 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                <span v-if="answers.q36">{{ getOptionLabel(answers.q36) }}</span>
                                                <span v-else class="font-bold text-gray-400">36</span>
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                @click.stop="toggleFlag(36)"
                                                class="absolute top-0 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(36) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </div>

                                        <!-- Q37 -->
                                        <div
                                            id="question-37"
                                            class="group relative"
                                            @mouseenter="hoveredQuestion = 37"
                                            @mouseleave="hoveredQuestion = null"
                                        >
                                            <p class="mb-2 mr-5 text-base leading-relaxed text-gray-800 select-text">
                                                <span data-segment-id="q37-text" v-html="getHighlightedSegment(`37. Additional support for Wilkinson's theory comes from findings that`)"></span>
                                            </p>
                                            <span
                                                class="inline-flex min-h-10 min-w-64 items-center justify-center rounded border-2 border-dashed px-3 py-2 text-center text-sm transition-all cursor-pointer"
                                                :class="dragOverQuestion === 37 ? 'border-blue-500 bg-blue-50' : answers.q37 ? 'border-gray-900 bg-white font-medium text-gray-900' : 'border-gray-400 bg-white'"
                                                @dragover="handleDragOver($event, 37)"
                                                @dragleave="handleDragLeave"
                                                @drop="handleDrop($event, 37)"
                                                @click="clearAnswer(37)"
                                                :title="answers.q37 ? 'Click to clear' : 'Drop answer here'"
                                            >
                                                <span v-if="answers.q37">{{ getOptionLabel(answers.q37) }}</span>
                                                <span v-else class="font-bold text-gray-400">37</span>
                                            </span>
                                            <button
                                                v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                                                @click.stop="toggleFlag(37)"
                                                class="absolute top-0 ml-1 right-0 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                                :class="isQuestionFlagged(37) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
                                            >
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                            </button>
                                        </div>

                                    </div>

                                    <!-- Right: Draggable options panel -->
                                    <div class="w-72 shrink-0 self-start sticky top-12">
                                        <p class="mb-3 text-sm font-semibold text-gray-700">Drag options to fill blanks:</p>
                                        <div class="border border-gray-900 bg-white p-3">
                                            <div class="space-y-2">
                                                <div
                                                    v-for="option in availableOptions"
                                                    :key="option.value"
                                                    class="flex cursor-grab items-start gap-2 rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                                                    :class="{ 'opacity-50': draggedOption === option.value }"
                                                    draggable="true"
                                                    @dragstart="handleDragStart($event, option.value)"
                                                    @dragend="handleDragEnd"
                                                >
                                                    <span class="shrink-0 font-bold text-gray-900 text-sm">{{ option.value }}.</span>
                                                    <span class="text-gray-700 text-sm leading-snug">{{ option.label }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ════════════════════════════════════
                                 Questions 38-40  (MCQ A/B/C/D radio)
                                 ════════════════════════════════════ -->
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="mb-2 text-base font-bold text-gray-900">
                                        <span
                                            data-segment-id="q38-40-title"
                                            v-html="getHighlightedSegment('Questions 38-40')"
                                        ></span>
                                    </h3>
                                    <div class=" bg-white p-4">
                                        <p class="text-base leading-relaxed text-gray-800">
                                            <span
                                                data-segment-id="q38-40-instructions"
                                                v-html="getHighlightedSegment('Choose the correct letter, A, B, C or D. Write your answers in boxes 38-40 on your answer sheet.')"
                                            ></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-6">

                                    <!-- Q38 -->
                                    <div
                                        id="question-38"
                                        class="relative  bg-white p-4 pr-10"
                                        @mouseenter="hoveredQuestion = 38"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-3 flex items-start gap-3">

                                            <p class="font-semibold text-base text-gray-800">
                                                <span
                                                    data-segment-id="q38-question"
                                                    v-html="getHighlightedSegment(`38. Wilkinson suggests that conservationists' concern about the island is misguided because`)"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="space-y-2 pl-11 text-base">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q38" type="radio" value="A" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q38-a" v-html="getHighlightedSegment('it is based on economic rather than environmental principles.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q38" type="radio" value="B" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q38-b" v-html="getHighlightedSegment('it is not focusing on the most important question.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q38" type="radio" value="C" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q38-c" v-html="getHighlightedSegment('it is encouraging the destruction of endemic species.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q38" type="radio" value="D" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q38-d" v-html="getHighlightedSegment('it is not supported by the local authorities.')"></span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                            @click.stop="toggleFlag(38)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(38) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q39 -->
                                    <div
                                        id="question-39"
                                        class="relative  bg-white p-4 pr-10"
                                        @mouseenter="hoveredQuestion = 39"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-3 flex items-start gap-3">

                                            <p class="font-semibold text-base text-gray-800">
                                                <span
                                                    data-segment-id="q39-question"
                                                    v-html="getHighlightedSegment('39. According to Wilkinson, studies of insects on the island could demonstrate')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="space-y-2 pl-11 text-base">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q39" type="radio" value="A" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q39-a" v-html="getHighlightedSegment('the possibility of new ecological relationships.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q39" type="radio" value="B" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q39-b" v-html="getHighlightedSegment('a future threat to the ecosystem of the island.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q39" type="radio" value="C" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q39-c" v-html="getHighlightedSegment('the existence of previously unknown species.')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q39" type="radio" value="D" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q39-d" v-html="getHighlightedSegment('a chance for the survival of rainforest ecology.')"></span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                            @click.stop="toggleFlag(39)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(39) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                    <!-- Q40 -->
                                    <div
                                        id="question-40"
                                        class="relative  bg-white p-4 pr-10"
                                        @mouseenter="hoveredQuestion = 40"
                                        @mouseleave="hoveredQuestion = null"
                                    >
                                        <div class="mb-3 flex items-start gap-3">

                                            <p class="font-semibold text-base text-gray-800">
                                                <span
                                                    data-segment-id="q40-question"
                                                    v-html="getHighlightedSegment('40. Overall, what feature of the Ascension rainforest does the writer stress?')"
                                                ></span>
                                            </p>
                                        </div>
                                        <div class="space-y-2 pl-11 text-base">
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q40" type="radio" value="A" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q40-a" v-html="getHighlightedSegment('the conflict of natural and artificial systems')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q40" type="radio" value="B" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q40-b" v-html="getHighlightedSegment('the unusual nature of its ecological structure')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q40" type="radio" value="C" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q40-c" v-html="getHighlightedSegment('the harm done by interfering with nature')"></span>
                                            </label>
                                            <label class="flex items-start gap-2 cursor-pointer">
                                                <input v-model="answers.q40" type="radio" value="D" class="mt-0.5 h-4 w-4 accent-black shrink-0" />
                                                <span data-segment-id="q40-d" v-html="getHighlightedSegment('the speed and success of its development')"></span>
                                            </label>
                                        </div>
                                        <button
                                            v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                            @click.stop="toggleFlag(40)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(40) ? 'border-gray-400 text-red-500' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Context Menu / Tooltips / Note Modal ── -->
        <Teleport to="body">

            <!-- Highlight + Note context menu -->
            <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                    @click.stop
                >
                    <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                        <button
                            @click="openNoteInput"
                            class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50"
                            title="Add Note"
                        >
                            <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
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
            <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
                <div
                    class="delete-highlight-tooltip fixed z-[9999]"
                    :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
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
            <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
                <div
                    class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                    :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                    @mouseleave="handleTooltipMouseLeave"
                    @click.stop
                >
                    <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                        <span class="shrink-0">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </span>
                        <span class="max-w-[180px] text-base break-words text-gray-700">{{ hoveredNoteText }}</span>
                        <button
                            @click="deleteNoteFromTooltip"
                            class="shrink-0 rounded p-1 transition-colors hover:bg-red-50"
                            title="Delete note"
                        >
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
                class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border border-gray-900 bg-white p-4 shadow-2xl"
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
                        class="note-input-field w-full border border-gray-900 px-3 py-2 text-base focus:border-black focus:outline-none"
                        @keyup.enter="saveNote"
                        @keyup.escape="cancelNote"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        @click="cancelNote"
                        class="border border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveNote"
                        class="bg-black px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-gray-800"
                    >
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
