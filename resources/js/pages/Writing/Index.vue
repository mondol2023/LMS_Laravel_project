<script setup>
import AppFooter from '@/components/Common/AppFooter.vue';
import { useHighlight } from '@/composables/useHighlight';
import PracticeLayout from '@/layouts/PracticeLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

defineOptions({
    layout: PracticeLayout,
});

// Get username from page props
const page = usePage();
const username = computed(() => page.props.username || page.props.auth?.user?.name || 'Guest');

const props = defineProps({
    subscription: {
        type: Object,
        default: null,
    },
    accessError: {
        type: String,
        default: null,
    },
});

// Reactive subscription state
const currentSubscription = ref(props.subscription);
const currentAccessError = ref(props.accessError);

// Track if limit was reached after submission (separate from initial access error)
const limitReachedAfterSubmission = ref(false);

const activeTaskCategory = ref('Task 1'); // 'Task 1' or 'Task 2'

// Exam mode state
const examStarted = ref(false);
const userAnswer = ref('');
const timeRemaining = ref(0);
const timerInterval = ref(null);
const examSubmitted = ref(false);
const showComparison = ref(false);

// AI Evaluation state
const isEvaluating = ref(false);
const evaluationResult = ref(null);
const evaluationError = ref(null);
const aiEvaluationRequested = ref(false);

// Footer clock and battery
const currentTime = ref('');
const batteryLevel = ref(100);
const isCharging = ref(false);
let clockInterval = null;

// WiFi connection status
const isOnline = ref(true);

// Options modal state
const showOptionsModal = ref(false);
const optionsView = ref('main'); // 'main' | 'contrast' | 'textSize' | 'instructions'

// Contrast theme
const contrastTheme = ref('black-on-white'); // 'black-on-white' | 'white-on-black' | 'yellow-on-black'

// Text size
const textSize = ref('regular'); // 'regular' | 'large' | 'extra-large'

// Privacy/Pause mode
const isPaused = ref(false);

// Notes drawer
const showNotesDrawer = ref(false);
const examNotes = ref([]);

// Highlight functionality
const { highlights, addHighlight, deleteHighlight, applyHighlightsToText } = useHighlight();

// Selection popup state
const showSelectionPopup = ref(false);
const selectionPopupPosition = ref({ x: 0, y: 0 });
const currentSelection = ref({ text: '', start: 0, end: 0, type: 'question' });
const questionContentRefMobile = ref(null);
const questionContentRefDesktop = ref(null);
const partHeaderRefMobile = ref(null);
const partHeaderRefDesktop = ref(null);

// Delete highlight tooltip state
const showDeleteTooltip = ref(false);
const deleteTooltipPosition = ref({ x: 0, y: 0 });
const deleteTooltipHighlightId = ref(null);

// Resize panel functionality
const PANEL_WIDTH_KEY = 'writing-practice-panel-width';
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref(null);

// Note modal state
const showNoteModal = ref(false);
const noteText = ref('');
const selectedTextForNote = ref('');

const updateClock = () => {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    currentTime.value = `${hours}:${minutes}`;
};

const updateBattery = async () => {
    try {
        if ('getBattery' in navigator) {
            const battery = await navigator.getBattery();
            batteryLevel.value = Math.round(battery.level * 100);
            isCharging.value = battery.charging;
        }
    } catch {
        batteryLevel.value = 85;
    }
};

// Word count computed
const wordCount = computed(() => {
    const text = userAnswer.value.trim();
    if (!text) return 0;
    return text.split(/\s+/).filter((word) => word.length > 0).length;
});

// Timer text display
const timerTextDisplay = computed(() => {
    const minutes = Math.floor(timeRemaining.value / 60);
    const seconds = timeRemaining.value % 60;
    if (minutes > 0) {
        return `${minutes} minute${minutes !== 1 ? 's' : ''} remaining`;
    }
    return `${seconds} second${seconds !== 1 ? 's' : ''} remaining`;
});

// Is time critical (less than 1 minute)
const isTimeCritical = computed(() => timeRemaining.value < 60);

// Header background class based on time
const headerBgClass = computed(() => {
    if (isTimeCritical.value) {
        return 'bg-red-100';
    }
    return 'bg-white';
});

// Header text class based on time
const headerTextClass = computed(() => {
    if (isTimeCritical.value) {
        return 'text-red-700';
    }
    return 'text-gray-900';
});

// Modal styling based on contrast theme
const modalBgClass = computed(() => {
    switch (contrastTheme.value) {
        case 'white-on-black':
            return 'bg-gray-900 text-white';
        case 'yellow-on-black':
            return 'bg-gray-900 text-yellow-400';
        default:
            return 'bg-white text-gray-900';
    }
});

const modalTextClass = computed(() => {
    switch (contrastTheme.value) {
        case 'white-on-black':
            return 'text-white';
        case 'yellow-on-black':
            return 'text-yellow-400';
        default:
            return 'text-gray-900';
    }
});

const modalBorderClass = computed(() => {
    return contrastTheme.value === 'black-on-white' ? 'border-gray-200' : 'border-gray-700';
});

const modalHoverClass = computed(() => {
    return contrastTheme.value === 'black-on-white' ? 'hover:bg-gray-50' : 'hover:bg-gray-800';
});

const modalTextSizeStyle = computed(() => {
    const sizeMap = { regular: 16, large: 20, 'extra-large': 24 };
    return { fontSize: `${sizeMap[textSize.value]}px` };
});

// Options modal functions
const openOptionsModal = () => {
    showOptionsModal.value = true;
    optionsView.value = 'main';
};

const closeOptionsModal = () => {
    showOptionsModal.value = false;
    optionsView.value = 'main';
};

const navigateToContrast = () => {
    optionsView.value = 'contrast';
};

const navigateToTextSize = () => {
    optionsView.value = 'textSize';
};

const navigateToInstructions = () => {
    optionsView.value = 'instructions';
};

const navigateBack = () => {
    optionsView.value = 'main';
};

// Contrast functions
const setContrastTheme = (theme) => {
    contrastTheme.value = theme;
};

// Text size functions
const setTextSize = (size) => {
    textSize.value = size;
};

// Pause/Privacy mode functions
const togglePause = () => {
    isPaused.value = true;
};

const resumeTest = () => {
    isPaused.value = false;
};

// Notes drawer functions
const toggleNotesDrawer = () => {
    showNotesDrawer.value = !showNotesDrawer.value;
};

const closeNotesDrawer = () => {
    showNotesDrawer.value = false;
};

const deleteExamNote = (noteId) => {
    examNotes.value = examNotes.value.filter((n) => n.id !== noteId);
};

// Get the full Part Header text (category + instructions)
const getPartHeaderText = () => {
    const category = activeTaskCategory.value;
    const instructions = activeTaskCategory.value === 'Task 1'
        ? 'You should spend about 20 minutes on this task. Write at least 150 words.'
        : 'You should spend about 40 minutes on this task. Write at least 250 words.';
    return `${category}\n${instructions}`;
};

// Get the original plain text (without any HTML marks)
const getOriginalQuestionText = () => {
    if (!activeTask.value) return '';
    return activeTaskCategory.value === 'Task 1'
        ? activeTask.value.content.description
        : `${activeTask.value.content.topicStatement}\n\n${activeTask.value.content.question}`;
};

// Get the active content element and its type (mobile or desktop, question or partHeader)
const getActiveContentElement = (selectionNode) => {
    // Check Part Header first (smaller area)
    if (partHeaderRefDesktop.value?.contains(selectionNode)) {
        return { element: partHeaderRefDesktop.value, type: 'partHeader' };
    }
    if (partHeaderRefMobile.value?.contains(selectionNode)) {
        return { element: partHeaderRefMobile.value, type: 'partHeader' };
    }
    // Then check question content
    if (questionContentRefDesktop.value?.contains(selectionNode)) {
        return { element: questionContentRefDesktop.value, type: 'question' };
    }
    if (questionContentRefMobile.value?.contains(selectionNode)) {
        return { element: questionContentRefMobile.value, type: 'question' };
    }
    return null;
};

// Handle text selection in question area or part header
const handleTextSelection = (event) => {
    // Small delay to ensure selection is complete
    setTimeout(() => {
        const selection = window.getSelection();
        const selected = selection?.toString().trim();

        if (!selected) {
            showSelectionPopup.value = false;
            return;
        }

        // Get selection range and position
        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();

        if (!rect) {
            return;
        }

        // Find which content element contains the selection
        const selectionAnchorNode = selection?.anchorNode;
        const activeContent = getActiveContentElement(selectionAnchorNode);

        if (!activeContent) {
            // Selection is outside content areas
            return;
        }

        const { element: activeContentElement, type: contentType } = activeContent;

        // Position tooltip ABOVE the selection with arrow pointing down
        const menuX = rect.left + rect.width / 2;
        const menuHeight = 50;
        const menuY = rect.top - menuHeight - 8;

        const viewportWidth = window.innerWidth;
        const menuWidth = 140;

        selectionPopupPosition.value = {
            x: Math.min(Math.max(menuX, menuWidth / 2 + 10), viewportWidth - menuWidth / 2 - 10),
            y: Math.max(menuY, 10),
        };

        // Get the appropriate original text based on content type
        const originalText = contentType === 'partHeader' ? getPartHeaderText() : getOriginalQuestionText();
        const startOffset = originalText.indexOf(selected);

        if (startOffset !== -1) {
            currentSelection.value = {
                text: selected,
                start: startOffset,
                end: startOffset + selected.length,
                type: contentType,
            };
            showSelectionPopup.value = true;
        } else {
            // Fallback: try to find with normalized whitespace
            const normalizedSelected = selected.replace(/\s+/g, ' ');
            const normalizedOriginal = originalText.replace(/\s+/g, ' ');
            const normalizedStart = normalizedOriginal.indexOf(normalizedSelected);

            if (normalizedStart !== -1) {
                // Map back to original text position
                let originalPos = 0;
                let normalizedPos = 0;
                while (normalizedPos < normalizedStart && originalPos < originalText.length) {
                    if (/\s/.test(originalText[originalPos])) {
                        while (originalPos < originalText.length && /\s/.test(originalText[originalPos])) {
                            originalPos++;
                        }
                        normalizedPos++;
                    } else {
                        originalPos++;
                        normalizedPos++;
                    }
                }
                currentSelection.value = {
                    text: selected,
                    start: originalPos,
                    end: originalPos + selected.length,
                    type: contentType,
                };
                showSelectionPopup.value = true;
            } else {
                // Last resort: use DOM-based calculation with text content extraction
                try {
                    const treeWalker = document.createTreeWalker(
                        activeContentElement,
                        NodeFilter.SHOW_TEXT,
                        null
                    );

                    let charCount = 0;
                    let foundStart = false;
                    let node;

                    while ((node = treeWalker.nextNode())) {
                        if (node === range.startContainer) {
                            charCount += range.startOffset;
                            foundStart = true;
                            break;
                        }
                        charCount += node.textContent?.length || 0;
                    }

                    if (foundStart) {
                        currentSelection.value = {
                            text: selected,
                            start: charCount,
                            end: charCount + selected.length,
                            type: contentType,
                        };
                        showSelectionPopup.value = true;
                    }
                } catch (e) {
                    console.warn('Could not calculate selection offset:', e);
                }
            }
        }
    }, 10);
};

// Apply highlight
const applyHighlight = (color) => {
    if (!currentSelection.value.text) return;

    // Remove overlapping notes (only for question type)
    if (currentSelection.value.type === 'question') {
        examNotes.value = examNotes.value.filter((n) => {
            return !(n.start < currentSelection.value.end && n.end > currentSelection.value.start);
        });
    }

    // Use the selection type as segment_id
    addHighlight(currentSelection.value.text, color, currentSelection.value.start, currentSelection.value.end, currentSelection.value.type);

    showSelectionPopup.value = false;
    window.getSelection()?.removeAllRanges();
};

// Handle click on content area (for delete highlight tooltip)
const handleContentClick = (event) => {
    const target = event.target;
    const highlightMark = target.closest('mark[data-highlight-id]');

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
            deleteTooltipHighlightId.value = parseInt(highlightId);
            showDeleteTooltip.value = true;
            showSelectionPopup.value = false;
        }
    }
};

// Close delete tooltip
const closeDeleteTooltip = () => {
    showDeleteTooltip.value = false;
    deleteTooltipHighlightId.value = null;
};

// Delete highlight from tooltip
const deleteHighlightFromTooltip = () => {
    if (deleteTooltipHighlightId.value) {
        // Also remove any associated note
        examNotes.value = examNotes.value.filter((n) => n.id !== deleteTooltipHighlightId.value);
        // Delete the highlight
        deleteHighlight(deleteTooltipHighlightId.value);
        closeDeleteTooltip();
    }
};

// Open note input
const openNoteInput = () => {
    if (!currentSelection.value.text) return;

    selectedTextForNote.value = currentSelection.value.text;

    const modalWidth = 320;
    const modalHeight = 180;
    const padding = 10;

    const selection = window.getSelection();
    let x, y;

    if (selection && selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const rect = range.getBoundingClientRect();
        x = rect.left + rect.width / 2;
        y = rect.bottom + 10;
    } else {
        x = selectionPopupPosition.value.x;
        y = selectionPopupPosition.value.y + 70;
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
    showNoteModal.value = true;
    showSelectionPopup.value = false;

    // Focus input after delay
    setTimeout(() => {
        const input = document.querySelector('.note-input-field');
        input?.focus();
    }, 100);
};

// Note input position
const noteInputPosition = ref({ x: 0, y: 0 });

// Cancel note
const cancelNote = () => {
    showNoteModal.value = false;
    noteText.value = '';
    selectedTextForNote.value = '';
    window.getSelection()?.removeAllRanges();
};

// Save note
const saveNote = () => {
    if (!currentSelection.value.text || !noteText.value.trim()) return;

    const noteId = Date.now();

    // Add to notes list
    examNotes.value.push({
        id: noteId,
        text: noteText.value.trim(),
        selectedText: selectedTextForNote.value,
        part: activeTaskCategory.value,
        start: currentSelection.value.start,
        end: currentSelection.value.end,
    });

    // Note is rendered via highlightedQuestionText computed - no separate highlight needed

    showNoteModal.value = false;
    noteText.value = '';
    selectedTextForNote.value = '';
    window.getSelection()?.removeAllRanges();
};

// Focus on note (scroll to highlighted text)
const focusOnNote = (note) => {
    // Try to find existing highlight mark
    const marks = document.querySelectorAll('mark[data-highlight-id]');
    let foundMark = null;

    for (const mark of marks) {
        if (mark.textContent?.trim() === note.selectedText.trim()) {
            foundMark = mark;
            break;
        }
    }

    if (foundMark) {
        foundMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
        foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
        setTimeout(() => {
            foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
        }, 2000);
    }

    closeNotesDrawer();
};

// Handle click outside to close popup
const handleClickOutside = (event) => {
    if (!showSelectionPopup.value) return;

    // Don't close if clicking inside the content areas (to allow text selection)
    const isInMobileContent = questionContentRefMobile.value?.contains(event.target);
    const isInDesktopContent = questionContentRefDesktop.value?.contains(event.target);
    const isInMobilePartHeader = partHeaderRefMobile.value?.contains(event.target);
    const isInDesktopPartHeader = partHeaderRefDesktop.value?.contains(event.target);
    if (isInMobileContent || isInDesktopContent || isInMobilePartHeader || isInDesktopPartHeader) return;

    // Don't close if clicking inside the popup itself
    const popup = document.querySelector('.highlight-tooltip');
    if (popup?.contains(event.target)) return;

    showSelectionPopup.value = false;
};

// Handle escape key
const handleKeyDown = (event) => {
    if (event.key === 'Escape') {
        showSelectionPopup.value = false;
        showNoteModal.value = false;
        showDeleteTooltip.value = false;
    }
};

// Resize handlers
const startResize = (event) => {
    isResizing.value = true;
    event.preventDefault();
};

const handleResize = (event) => {
    if (!isResizing.value || !containerRef.value) return;

    const containerRect = containerRef.value.getBoundingClientRect();
    const offsetX = event.clientX - containerRect.left;
    const newLeftWidth = (offsetX / containerRect.width) * 100;

    // Set minimum and maximum widths (20% to 80%)
    if (newLeftWidth >= 20 && newLeftWidth <= 80) {
        leftPanelWidth.value = newLeftWidth;
        localStorage.setItem(PANEL_WIDTH_KEY, newLeftWidth.toString());
    }
};

const stopResize = () => {
    isResizing.value = false;
};

/// Computed: Apply highlights to question text
const highlightedQuestionText = computed(() => {
    if (!activeTask.value) return '';

    let text = activeTaskCategory.value === 'Task 1'
        ? activeTask.value.content.description
        : `${activeTask.value.content.topicStatement}\n\n${activeTask.value.content.question}`;

    text = applyHighlightsToText(text, 'question');

    // Add note indicators
    if (examNotes.value.length > 0) {
        for (const note of examNotes.value) {
            const escapedText = note.selectedText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const highlightPattern = new RegExp(`(<mark[^>]*>)(${escapedText})(</mark>)`, 'i');

            const beforeReplace = text;
            text = text.replace(highlightPattern, (match, openTag, content, closeTag) => {
                if (content.includes('note-highlight')) return match;
                return `${openTag}<mark class="note-highlight" data-note-id="${note.id}">${content}</mark>${closeTag}`;
            });

            if (text === beforeReplace) {
                const plainPattern = new RegExp(`(?<!<[^>]*)(${escapedText})(?![^<]*>)`, '');
                let replaced = false;
                text = text.replace(plainPattern, (match) => {
                    if (replaced || match.includes('<')) return match;
                    replaced = true;
                    return `<mark class="note-highlight" data-note-id="${note.id}">${match}</mark>`;
                });
            }
        }
    }

    return text;
});

// Computed: Apply highlights to part header text
const highlightedPartHeaderText = computed(() => {
    let text = getPartHeaderText();

    // Filter highlights for partHeader segment only
    const partHeaderHighlights = highlights.value.filter(h => h.segment_id === 'partHeader');

    if (partHeaderHighlights.length > 0) {
        // Sort highlights by start offset in descending order
        const sortedHighlights = [...partHeaderHighlights].sort((a, b) => b.start_offset - a.start_offset);

        for (const highlight of sortedHighlights) {
            const before = text.substring(0, highlight.start_offset);
            const highlighted = text.substring(highlight.start_offset, highlight.end_offset);
            const after = text.substring(highlight.end_offset);

            text = `${before}<mark class="highlight-${highlight.color}" data-highlight-id="${highlight.id}">${highlighted}</mark>${after}`;
        }
    }

    // Split into category (bold) and instructions, with proper styling
    const parts = text.split('\n');
    if (parts.length >= 2) {
        return `<p class="text-sm font-bold text-gray-900 sm:text-base">${parts[0]}</p><p class="text-sm text-gray-700">${parts.slice(1).join(' ')}</p>`;
    }
    return text;
});

// WiFi status check
const updateConnectionStatus = () => {
    isOnline.value = navigator.onLine;
};

// Start exam
const startExam = () => {
    if (!activeTask.value) return;
    examStarted.value = true;
    examSubmitted.value = false;
    userAnswer.value = '';
    evaluationResult.value = null;
    evaluationError.value = null;
    examNotes.value = []; // Clear notes for new exam
    highlights.value = []; // Clear highlights for new exam
    timeRemaining.value = activeTask.value.content.time * 60; // Convert minutes to seconds

    // Start clock and battery updates
    updateClock();
    updateBattery();
    clockInterval = setInterval(updateClock, 10000);

    // Setup WiFi listeners
    window.addEventListener('online', updateConnectionStatus);
    window.addEventListener('offline', updateConnectionStatus);
    updateConnectionStatus();

    // Setup click outside and keydown listeners
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);

    // Setup resize event listeners
    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);

    timerInterval.value = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;
        } else {
            submitExam();
        }
    }, 1000);
};

// Cancel exam
const cancelExam = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
    if (clockInterval) {
        clearInterval(clockInterval);
        clockInterval = null;
    }
    window.removeEventListener('online', updateConnectionStatus);
    window.removeEventListener('offline', updateConnectionStatus);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
    examStarted.value = false;
    userAnswer.value = '';
    evaluationResult.value = null;
    evaluationError.value = null;
    isPaused.value = false;
    showOptionsModal.value = false;
    showNotesDrawer.value = false;
    showSelectionPopup.value = false;
    showNoteModal.value = false;
    showDeleteTooltip.value = false;
    examNotes.value = [];
    highlights.value = [];
};

// Get CSRF token
const getCsrfToken = () => {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
};

// Submit exam - show comparison view (no AI evaluation yet)
const submitExam = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
    if (clockInterval) {
        clearInterval(clockInterval);
        clockInterval = null;
    }

    if (!userAnswer.value.trim()) {
        evaluationError.value = 'Please write something before submitting.';
        return;
    }

    examSubmitted.value = true;
    showComparison.value = true;
    evaluationError.value = null;
};

// Get AI Feedback - uses subscription quota
const getAiFeedback = async () => {
    if (aiEvaluationRequested.value || isEvaluating.value) return;

    isEvaluating.value = true;
    aiEvaluationRequested.value = true;
    evaluationError.value = null;

    try {
        const currentTask = activeTask.value;
        const question =
            activeTaskCategory.value === 'Task 1'
                ? currentTask.content.description
                : `${currentTask.content.topicStatement} ${currentTask.content.question}`;

        const writingTasks = [{ q: question, ans: userAnswer.value }];

        const response = await fetch('/api/writing/practice-evaluate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ writing: writingTasks }),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to evaluate writing.');
        }

        evaluationResult.value = result.data;

        if (result.subscription) {
            currentSubscription.value = result.subscription;
            if (result.subscription.practice_writing_remaining === 0) {
                limitReachedAfterSubmission.value = true;
            }
        }
    } catch (error) {
        evaluationError.value = error instanceof Error ? error.message : 'An unexpected error occurred.';
        aiEvaluationRequested.value = false;
        console.error('AI Evaluation Error:', error);
    } finally {
        isEvaluating.value = false;
    }
};

// Calculate overall band score for displayed task
const overallBandScore = computed(() => {
    if (!evaluationResult.value) return null;

    if (activeTaskCategory.value === 'Task 1') {
        const { task1_ta, task1_cc, task1_lr, task1_gra } = evaluationResult.value;
        const avg = (task1_ta + task1_cc + task1_lr + task1_gra) / 4;
        return (Math.round(avg * 2) / 2).toFixed(1);
    } else {
        const { task2_ta, task2_cc, task2_lr, task2_gra } = evaluationResult.value;
        const avg = (task2_ta + task2_cc + task2_lr + task2_gra) / 4;
        return (Math.round(avg * 2) / 2).toFixed(1);
    }
});

// Try another task
const tryAnotherTask = () => {
    if (limitReachedAfterSubmission.value) {
        return;
    }

    examStarted.value = false;
    examSubmitted.value = false;
    showComparison.value = false;
    aiEvaluationRequested.value = false;
    userAnswer.value = '';
    evaluationResult.value = null;
    evaluationError.value = null;
};

// Reset exam state (used when changing tasks)
const resetExamState = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
    if (clockInterval) {
        clearInterval(clockInterval);
        clockInterval = null;
    }
    examStarted.value = false;
    examSubmitted.value = false;
    showComparison.value = false;
    aiEvaluationRequested.value = false;
    userAnswer.value = '';
    evaluationResult.value = null;
    evaluationError.value = null;
    isEvaluating.value = false;
    timeRemaining.value = 0;
};

// Handle task selection from sidebar
const selectTask = (task) => {
    if (activeTask.value?.id === task.id) return; // Same task, do nothing

    // Reset exam state when changing tasks
    resetExamState();
    activeTask.value = task;
};

// Handle category change (Task 1 / Task 2)
const selectCategory = (category) => {
    if (activeTaskCategory.value === category) return; // Same category, do nothing

    // Reset exam state when changing category
    resetExamState();
    activeTaskCategory.value = category;
};

// Cleanup on unmount
onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
    if (clockInterval) {
        clearInterval(clockInterval);
    }
    window.removeEventListener('online', updateConnectionStatus);
    window.removeEventListener('offline', updateConnectionStatus);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
});

// --- Task 1 Data ---
const task1QuestionTypes = ['All', 'Bar Chart', 'Table', 'Map', 'Line Graph', 'Combination Chart', 'Pie Chart', 'Process Diagram'];
const activeTask1QuestionType = ref('All');
const task1Tasks = [
    {
        id: 1,
        name: 'C20 Test4 Process Diagram',
        type: 'Process Diagram',
        content: {
            time: 20,
            words: 150,
            description:
                'The diagram below shows how fabric is manufactured from bamboo. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.',
            image: '/images/writing/C20P4.png',
            sampleAnswer1:
                'The diagram illustrates the manufacturing process of bamboo fabric, which comprises several distinct stages from raw material cultivation to the final textile product.\n\nInitially, bamboo is cultivated and harvested when it reaches maturity. The harvested bamboo stalks are subsequently transported to a processing facility where they undergo pulping. During this stage, the bamboo is broken down into fibrous material through mechanical and chemical processes.\n\nFollowing pulping, the bamboo fibres are subjected to a bleaching procedure to remove natural pigments and impurities. The bleached fibres are then dried thoroughly before being spun into yarn using industrial spinning machinery. This yarn serves as the fundamental component for fabric production.\n\nIn the final stages, the bamboo yarn is woven on looms to create the fabric. The completed fabric undergoes quality inspection before being dispatched for commercial distribution.\n\nOverall, the transformation of bamboo into fabric involves a sophisticated sequence of industrial processes, beginning with agricultural cultivation and culminating in a finished textile product suitable for various applications.',
            sampleAnswer2:
                'The process diagram delineates the sequential steps involved in converting bamboo into wearable fabric, encompassing both agricultural and industrial phases.\n\nThe procedure commences with the cultivation of bamboo plants, which are harvested upon reaching optimal growth. These raw bamboo stalks are then transported to manufacturing facilities for further processing.\n\nSubsequently, the bamboo undergoes pulping, wherein the woody material is decomposed into cellulose fibres. This pulp is then bleached to achieve the desired colour consistency and remove any remaining impurities. The resulting material is dried using industrial equipment to eliminate moisture content.\n\nThe dried bamboo fibres are thereafter processed through spinning machines, which twist the fibres into continuous yarn threads. This yarn is subsequently woven into fabric using automated looms, creating the textile material.\n\nThe concluding stage involves quality assessment, after which the fabric is prepared for distribution to manufacturers and retailers.\n\nIn summary, bamboo fabric production requires a multi-stage manufacturing process that transforms raw plant material into a versatile textile through pulping, bleaching, drying, spinning, and weaving procedures.',
        },
    },
    {
        id: 2,
        name: 'C20 Test3 Combination Chart',
        type: 'Combination Chart',
        content: {
            time: 20,
            words: 150,
            description:
                'Write about a combination chart. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.',
            image: '/images/writing/C20P3.png',
            sampleAnswer1:
                'The charts present data regarding multiple variables, displaying their interrelationships and trends over the given period.\n\nExamining the primary data set, it is evident that there was a significant upward trajectory throughout the measured timeframe. The figures commenced at a relatively modest level before experiencing substantial growth, ultimately reaching their peak towards the end of the period.\n\nConversely, the secondary data demonstrates a contrasting pattern. While initially recording higher values, this category experienced a gradual decline, eventually stabilising at lower levels compared to its starting point.\n\nWhen comparing the two data sets, it becomes apparent that an inverse relationship exists between them. As one variable increased, the other correspondingly decreased, suggesting a potential correlation between these factors.\n\nThe most notable observation is the intersection point where both variables achieved parity, marking a significant shift in their relative positions.\n\nIn conclusion, the combination chart effectively illustrates the dynamic relationship between the measured variables, highlighting their divergent trends and eventual convergence.',
            sampleAnswer2:
                'The combination chart illustrates comparative data across multiple categories, revealing significant patterns and variations throughout the observed period.\n\nThe bar chart component indicates that the largest category accounted for the majority share, substantially exceeding other segments. This dominant category maintained its leading position consistently, demonstrating remarkable stability.\n\nMeanwhile, the line graph overlay depicts temporal changes in the overall trend. Starting from an initial baseline, the trajectory showed consistent upward momentum, with only minor fluctuations interrupting the general pattern of growth.\n\nA noteworthy comparison reveals that while absolute values in certain categories remained relatively static, percentage distributions underwent considerable transformation.\n\nFurthermore, seasonal variations are discernible within the data, with predictable peaks and troughs occurring at regular intervals throughout the timeline.\n\nOverall, the combination chart effectively synthesises multiple data dimensions, demonstrating both the static proportional relationships and dynamic temporal changes that characterise this dataset.',
        },
    },
    {
        id: 3,
        name: 'C20 Test2 Map',
        type: 'Map',
        content: {
            time: 20,
            words: 150,
            description:
                'Describe a map showing changes over time. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.',
            image: '/images/writing/C20P2.png',
            sampleAnswer1:
                'The maps illustrate the transformation of an area between two distinct time periods, revealing substantial developmental changes in land use and infrastructure.\n\nIn the earlier period, the area was predominantly characterised by agricultural land and sparse residential properties. A main road traversed the region from north to south, with limited auxiliary pathways connecting isolated farmhouses.\n\nBy the later period, dramatic urbanisation had occurred. The agricultural land had been largely converted into residential zones, featuring organised housing developments and commercial establishments. The road network had expanded significantly, with new connecting roads and roundabouts facilitating improved traffic flow.\n\nNotably, previously open spaces in the central area were replaced by a shopping complex, while the eastern section witnessed the construction of recreational facilities including a park and sports grounds.\n\nThe northern boundary, which originally contained woodland, was partially cleared to accommodate an industrial zone, representing a shift towards mixed-use development.\n\nIn summary, the maps demonstrate a comprehensive transformation from a rural landscape to an urbanised area with diverse residential, commercial, and recreational facilities.',
            sampleAnswer2:
                'The two maps present a comparative analysis of geographical changes that have occurred in the depicted area over time, highlighting significant urban development and infrastructure modifications.\n\nInitially, the landscape was predominantly rural, featuring expansive farmland interspersed with occasional residential dwellings. The river flowing through the area remained the sole prominent natural feature, with minimal human intervention along its banks.\n\nIn stark contrast, the contemporary map reveals extensive modernisation. The former agricultural zones have been superseded by systematic residential developments, arranged in geometric patterns characteristic of planned housing estates.\n\nTransportation infrastructure has undergone considerable enhancement, with the introduction of a bypass road circumventing the central area and multiple junction improvements facilitating smoother vehicular movement. A new bridge now spans the river, connecting previously isolated communities.\n\nAdditionally, community amenities have been introduced, including educational institutions in the western sector and healthcare facilities adjacent to the main residential areas.\n\nThe transformation exemplifies typical suburban expansion, wherein rural landscapes are progressively converted into integrated urban environments.',
        },
    },
    {
        id: 4,
        name: 'C20 Test1 Table',
        type: 'Table',
        content: {
            time: 20,
            words: 150,
            description:
                'Analyze the data in the table. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.',
            image: '/images/writing/C20P1.png',
            sampleAnswer1:
                'The table provides statistical data comparing various categories across different parameters, revealing notable patterns and disparities.\n\nExamining the highest values, Category A demonstrates superior performance, recording figures substantially above the overall average. This category consistently outperforms others across multiple measured criteria, establishing itself as the dominant segment.\n\nConversely, Category D registers the lowest values throughout, indicating underperformance relative to its counterparts. The disparity between the highest and lowest performers is particularly pronounced, with a differential exceeding fifty percent in several instances.\n\nThe intermediate categories, B and C, display relatively comparable statistics, occupying the middle ground between the extremes. However, subtle variations exist, with Category B marginally surpassing Category C in most parameters.\n\nTemporal analysis reveals that all categories experienced growth during the measured period, albeit at varying rates. Category A demonstrated the most substantial increase, while Category D showed the most modest improvement.\n\nIn conclusion, the data illustrates significant variation between categories, with a clear hierarchy evident in the performance metrics presented.',
            sampleAnswer2:
                'The tabulated data presents a comprehensive comparison across multiple categories, enabling detailed analysis of relative performance and underlying trends.\n\nThe most striking observation concerns the substantial variation between the maximum and minimum recorded values. The leading category accounts for approximately double the figures of the lowest performer, indicating a significant disparity within the dataset.\n\nRegional breakdown reveals geographical clustering of high-performing areas, predominantly concentrated in developed sectors. Conversely, developing regions consistently register below-average statistics, suggesting a correlation between developmental status and measured outcomes.\n\nYear-on-year comparison demonstrates overall upward momentum across all categories, with growth rates ranging from modest single-digit increases to more substantial double-digit improvements.\n\nFurthermore, the data reveals seasonal patterns, with predictable fluctuations corresponding to cyclical factors affecting the measured parameters.\n\nOverall, the table effectively summarises complex multi-dimensional data, facilitating comparison and identification of significant patterns requiring further investigation or policy intervention.',
        },
    },
    { id: 5, name: 'C19 Test4 Combination Chart', type: 'Combination Chart', content: { time: 20, words: 150, description: 'Write about a combination chart. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C19P4.png' } },
    { id: 6, name: 'C19 Test3 Process Diagram', type: 'Process Diagram', content: { time: 20, words: 150, description: 'Describe a process diagram. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C19P3.png' } },
    { id: 7, name: 'C19 Test2 Map', type: 'Map', content: { time: 20, words: 150, description: 'Describe a map showing changes over time. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C19P2.png' } },
    { id: 8, name: 'C19 Test1 Line Graph', type: 'Line Graph', content: { time: 20, words: 150, description: 'Analyze the line graph. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C19P1.png' } },
    { id: 9, name: 'C18 Test4 Line Graph', type: 'Line Graph', content: { time: 20, words: 150, description: 'Analyze the line graph. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C18P4.png' } },
    { id: 10, name: 'C18 Test3 Map', type: 'Map', content: { time: 20, words: 150, description: 'Describe a map showing changes over time. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C18P3.png' } },
    { id: 11, name: 'C18 Test2 Bar Chart', type: 'Bar Chart', content: { time: 20, words: 150, description: 'Analyze the bar chart. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C18P2.png' } },
    { id: 12, name: 'C18 Test1 Line Graph', type: 'Line Graph', content: { time: 20, words: 150, description: 'Analyze the line graph. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C18P1.png' } },
    { id: 13, name: 'C17 Test4 Line Graph', type: 'Line Graph', content: { time: 20, words: 150, description: 'The graph below gives information about the percentage of the population in four Asian countries living in cities from 1970 to 2020, with predictions for 2030 and 2040. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C17P4.png' } },
    { id: 14, name: 'C17 Test3 Bar Chart', type: 'Bar Chart', content: { time: 20, words: 150, description: 'The chart below gives information about how families in one country spent their weekly income in 1968 and in 2018. Summarise the information by selecting and reporting the main features, and make comparisons where relevant. ', image: '/images/writing/C17P3.png' } },
    { id: 15, name: 'C17 Test2 Combination Chart', type: 'Combination Chart', content: { time: 20, words: 150, description: 'The table and charts below give information on the police budget for 2017 and 2018 in one area of Britain. The table shows where the money came from and the charts show how it was distributed. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', image: '/images/writing/C17P2.png' } },
    { id: 16, name: 'C17 Test1 Map', type: 'Map', content: { time: 20, words: 150, description: 'The maps below show an industrial area in the town of Norbiton, and planned future development of the site. Summarise the information by selecting and reporting the main features, and make comparisons where relevant. ', image: '/images/writing/C17P1.png' } },
];

// --- Task 2 Data ---
const task2QuestionTypes = ['All', 'Opinion', 'Advantages & Disadvantages', 'Discuss', 'Report'];
const activeTask2QuestionType = ref('All');
const task2Topics = ['All', 'Lifestyle', 'Economics', 'Government', 'Education', 'Language', 'Social Media', 'Technology', 'Environment'];
const activeTask2Topic = ref('All');

const task2Tasks = [
    { id: 1, name: 'C20 Test4 Advantage...', type: 'Advantages & Disadvantages', topic: 'Lifestyle', content: { time: 40, words: 250, topicStatement: 'Many aspects of the way people dress today are influenced by global fashion trends.', question: "How has global fashion become such a strong influence on people's lives? Do you think this is a positive or negative development?", answeringIdeasLink: '#', sampleAnswer1: 'Global fashion has become an overwhelmingly powerful force in contemporary society, fundamentally reshaping how individuals perceive and present themselves. This essay will examine the mechanisms through which fashion exerts its influence and argue that, despite certain drawbacks, this phenomenon represents a predominantly positive development.\n\nThe proliferation of global fashion trends can be attributed to several interconnected factors. Foremost among these is the unprecedented reach of social media platforms, which enable instantaneous dissemination of style inspiration across geographical boundaries. Furthermore, multinational fashion corporations have established ubiquitous retail presences, making trendy clothing accessible to consumers worldwide.\n\nI contend that this globalisation of fashion constitutes a largely positive development for several compelling reasons. Firstly, it democratises access to aesthetic self-expression, enabling individuals regardless of location to participate in contemporary style movements. Secondly, it fosters cultural exchange, as design elements from diverse traditions gain international appreciation.\n\nNevertheless, legitimate concerns exist regarding environmental sustainability and the potential homogenisation of cultural dress traditions. These challenges, however, are increasingly being addressed through sustainable fashion initiatives.\n\nIn conclusion, while vigilance regarding fashion\'s environmental and cultural impacts remains necessary, the globalisation of fashion has predominantly enhanced individual expression and cross-cultural appreciation.', sampleAnswer2: 'The contemporary fashion industry has evolved into a remarkably influential global phenomenon, profoundly affecting consumer behaviour, self-identity, and cultural expression worldwide. This essay will analyse the factors contributing to fashion\'s pervasive influence and evaluate whether this development should be regarded positively or negatively.\n\nSeveral factors have converged to establish fashion\'s dominant position in modern life. The digitalisation of media has created unprecedented connectivity, enabling fashion trends to traverse continents instantaneously. International fashion weeks, celebrity endorsements, and sophisticated marketing campaigns have elevated clothing choices into statements of identity and social belonging.\n\nOn balance, I believe this represents a positive development, albeit one requiring thoughtful engagement. Global fashion provides individuals with expanded opportunities for self-expression and creativity, transcending traditional constraints of geography and socioeconomic status. It has created substantial economic opportunities, generating employment across design, manufacturing, retail, and media sectors globally.\n\nCritics justifiably highlight concerns regarding environmental degradation and labour exploitation within supply chains. However, growing consumer awareness is driving industry reforms, with sustainable and ethical fashion gaining significant market traction.\n\nIn summary, the globalisation of fashion, despite requiring ongoing improvement in sustainability and ethical practices, has fundamentally expanded human creative expression and cultural exchange.' } },
    { id: 2, name: 'C20 Test3 Discuss Technology', type: 'Discuss', topic: 'Technology', content: { time: 40, words: 250, topicStatement: 'Some people believe that technology has made our lives too complicated, while others think it has simplified everyday tasks.', question: 'Discuss both views and give your own opinion.', answeringIdeasLink: '#', sampleAnswer1: 'The impact of technological advancement on daily life remains a subject of considerable debate, with perspectives ranging from enthusiastic embrace to cautious scepticism. This essay will examine both viewpoints before presenting my own assessment.\n\nThose who contend that technology has complicated existence raise several valid concerns. The constant connectivity enabled by smartphones and computers has blurred boundaries between professional and personal time, creating expectations of perpetual availability that generate stress. Moreover, the proliferation of options and information sources can induce decision paralysis and information overload.\n\nConversely, proponents of technology emphasise its transformative simplification of previously laborious tasks. Communication across distances that once required weeks now occurs instantaneously. Banking, shopping, and administrative tasks that previously demanded physical presence are now accomplished through brief digital interactions.\n\nIn my assessment, technology fundamentally simplifies life when approached intentionally. The apparent complications arise primarily from inadequate digital literacy and insufficient boundary-setting rather than from technology itself. Individuals who develop competence in managing digital tools overwhelmingly benefit from technological conveniences.\n\nIn conclusion, while technology presents genuine challenges requiring thoughtful navigation, its overall effect has been to liberate human time and expand possibilities, representing a net positive for quality of life when engaged with appropriately.', sampleAnswer2: 'Technological innovation has fundamentally transformed contemporary existence, yet assessments of its impact vary considerably. While some individuals perceive technology as an unwelcome complication of daily life, others celebrate its capacity to streamline routine activities. This essay will analyse both perspectives and articulate my personal position.\n\nAdvocates of the complexity thesis highlight several concerning developments. Digital devices generate constant notifications and demands for attention, fragmenting concentration and increasing cognitive load. The necessity of maintaining multiple passwords, understanding evolving platforms, and troubleshooting technical difficulties introduces frustrations absent from previous generations\' experiences.\n\nHowever, the simplification argument presents equally compelling evidence. Household appliances have dramatically reduced time devoted to domestic labour, while digital communication has revolutionised both personal relationships and professional collaboration. Navigation applications have eliminated the challenges of unfamiliar travel.\n\nMy personal view acknowledges both perspectives while ultimately endorsing technology\'s positive contribution. The complications cited typically reflect transitional challenges or user behaviours rather than inherent technological deficiencies. As digital literacy improves, these complications diminish while benefits compound.\n\nTo conclude, technology\'s net effect has been overwhelmingly positive in simplifying essential life functions, though realising these benefits requires ongoing adaptation and intentional engagement with digital tools.' } },
    { id: 3, name: 'C20 Test2 Opinion Education', type: 'Opinion', topic: 'Education', content: { time: 40, words: 250, topicStatement: 'Some people think that universities should provide graduates with the knowledge and skills needed in the workplace.', question: 'To what extent do you agree or disagree with this opinion?', answeringIdeasLink: '#', sampleAnswer1: 'The role of universities in preparing students for professional life has become increasingly contested in contemporary educational discourse. While some advocate for vocational orientation, I partially agree that universities should incorporate workplace-relevant preparation while maintaining their broader educational mission.\n\nProponents of vocational university education present compelling arguments. Graduates face intensifying competition in labour markets, making employment-ready skills increasingly valuable. Employers frequently report dissatisfaction with graduates\' practical capabilities, suggesting misalignment between academic curricula and professional requirements.\n\nHowever, excessive vocationalisation risks compromising universities\' fundamental purposes. Higher education traditionally cultivates critical thinking, intellectual curiosity, and broad cultural literacy - capacities transcending specific occupational applications. These foundational competencies enable graduates to adapt to evolving professional landscapes.\n\nMy position advocates balanced integration rather than prioritisation of either approach. Universities should ensure graduates possess transferable skills - communication, analytical reasoning, digital literacy - applicable across diverse professional contexts. Simultaneously, they should preserve space for intellectual exploration and personal development.\n\nIn conclusion, while universities should acknowledge legitimate expectations regarding employability, they must resist reduction to mere vocational training institutions. The optimal approach integrates workplace-relevant preparation within a broader educational framework cultivating adaptable, thoughtful graduates.', sampleAnswer2: 'The question of whether universities should prioritise employment-oriented education has generated considerable debate among educators, employers, and students alike. This essay argues that while vocational preparation deserves attention, it should not constitute universities\' primary purpose.\n\nArguments favouring vocational orientation appear superficially persuasive. Students invest substantial resources in higher education with reasonable expectations of career advancement. Skills shortages in various sectors suggest potential misalignment between educational outputs and economic needs.\n\nNevertheless, I substantially disagree with the proposition that workplace preparation should define university education. Universities fulfil crucial functions beyond employment training: they cultivate informed citizenship, advance human knowledge through research, and enable personal intellectual development. These contributions possess intrinsic value independent of labour market considerations.\n\nThe most effective educational approach recognises complementarity rather than competition between vocational and academic objectives. Universities should develop graduates\' general capabilities - critical analysis, effective communication, collaborative problem-solving - which prove valuable across diverse professional contexts.\n\nIn conclusion, universities should resist pressure to transform into vocational training institutions while thoughtfully incorporating transferable skill development within broader educational frameworks. This balanced approach serves both graduates\' career aspirations and society\'s need for educated, reflective citizens.' } },
    { id: 4, name: 'C20 Test1 Problem Solution', type: 'Report', topic: 'Environment', content: { time: 40, words: 250, topicStatement: 'In many countries, the amount of household waste is increasing.', question: 'What are the causes of this? What solutions can governments and individuals implement?', answeringIdeasLink: '#', sampleAnswer1: 'The escalating volume of household waste represents a pressing environmental challenge confronting numerous nations worldwide. This essay will examine the principal causes of this phenomenon and propose actionable solutions involving both governmental and individual initiatives.\n\nSeveral factors contribute to increasing domestic waste production. Consumer culture, actively promoted through sophisticated marketing, encourages frequent purchasing and rapid disposal of goods. Product design often prioritises aesthetics and cost reduction over durability, resulting in premature obsolescence. Furthermore, excessive packaging, particularly single-use plastics, substantially augments waste volumes.\n\nGovernments possess considerable capacity to address this challenge through policy interventions. Implementing extended producer responsibility schemes would incentivise manufacturers to design products with end-of-life considerations. Investing in comprehensive recycling infrastructure and public education campaigns would increase diversion from landfills.\n\nIndividual actions, while individually modest, collectively constitute significant impact. Adopting mindful consumption practices - questioning necessity before purchases - fundamentally reduces waste generation. Choosing products with minimal packaging, repairing rather than replacing goods, and composting organic waste further diminish household contributions.\n\nIn conclusion, addressing escalating household waste requires coordinated action across governmental and individual spheres. Through progressive policy frameworks and conscientious personal choices, societies can fundamentally transform consumption patterns.', sampleAnswer2: 'The proliferation of household waste has emerged as a significant environmental concern across developed and developing nations alike. This essay will analyse the underlying causes driving this trend and propose practical solutions implementable by both authorities and citizens.\n\nThe causes of increased household waste are multifaceted. Economic prosperity has enabled greater consumption, while advertising cultivates desires for novelty goods. The convenience economy has normalised disposable products, from single-use containers to fast fashion. Additionally, inadequate waste management infrastructure in many regions means materials that could be recycled instead enter landfills.\n\nGovernmental responses should address both supply and demand dimensions. Legislation mandating minimum product lifespans and repairability would counter planned obsolescence. Deposit-return schemes for containers and comprehensive kerbside recycling programmes would increase recovery rates.\n\nIndividual contributions, while seemingly modest, prove collectively transformative. Embracing minimalist consumption philosophies reduces material throughput fundamentally. Selecting durable, repairable products over disposable alternatives extends utility cycles. Supporting businesses demonstrating environmental responsibility reinforces sustainable market trends.\n\nTo conclude, reversing household waste growth requires systemic transformation encompassing production practices, consumption patterns, and waste processing infrastructure. Coordinated governmental action combined with widespread individual behavioural change can establish circular economy principles.' } },
];

// --- Computed properties for current active task category ---
const currentQuestionTypes = computed(() => {
    return activeTaskCategory.value === 'Task 1' ? task1QuestionTypes : task2QuestionTypes;
});

const currentTopics = computed(() => {
    return activeTaskCategory.value === 'Task 2' ? task2Topics : [];
});

const currentTasks = computed(() => {
    return activeTaskCategory.value === 'Task 1' ? task1Tasks : task2Tasks;
});

const filteredTasks = computed(() => {
    let filtered = currentTasks.value;

    if (activeTaskCategory.value === 'Task 1') {
        if (activeTask1QuestionType.value !== 'All') {
            filtered = filtered.filter((task) => task.type === activeTask1QuestionType.value);
        }
    } else {
        // Task 2
        if (activeTask2QuestionType.value !== 'All') {
            filtered = filtered.filter((task) => task.type === activeTask2QuestionType.value);
        }
        if (activeTask2Topic.value !== 'All') {
            filtered = filtered.filter((task) => task.topic === activeTask2Topic.value);
        }
    }
    return filtered;
});

const activeTask = ref(filteredTasks.value[0]);

// Watchers
watch(filteredTasks, (newFilteredTasks) => {
    // Reset exam state when task list changes (due to filter changes)
    resetExamState();

    if (newFilteredTasks.length > 0) {
        activeTask.value = newFilteredTasks[0];
    } else {
        activeTask.value = null;
    }
});

// Reset filters when changing task category
watch(activeTaskCategory, () => {
    activeTask1QuestionType.value = 'All';
    activeTask2QuestionType.value = 'All';
    activeTask2Topic.value = 'All';
});
</script>

<template>
    <Head :title="`Writing ${activeTaskCategory}`" />

    <!-- Full Screen Exam Mode (Teleported to body) -->
    <Teleport to="body">
        <div v-if="examStarted" class="fixed inset-0 z-[9999] flex flex-col bg-white">
            <!-- Custom Header (exactly like IELTS002 WritingHeader) -->
            <nav class="sticky top-0 z-50" :class="headerBgClass">
                <!-- Main Header -->
                <div class="flex h-12 items-center justify-between px-2 sm:h-18 sm:px-4">
                    <!-- Left Section: Logo + User Info -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- IELTS Logo -->
                        <img src="/images/LandingUI/ielts.png" alt="IELTS" class="ml-4 h-12 w-auto object-contain sm:h-17" />

                        <!-- User Info -->
                        <div class="flex flex-col leading-tight">
                            <span class="text-xs font-bold sm:text-sm" :class="headerTextClass">
                                {{ username }}
                            </span>
                            <span class="text-[10px] opacity-90 sm:text-xs" :class="headerTextClass">
                                {{ timerTextDisplay }}
                            </span>
                        </div>
                    </div>

                    <!-- Right Section: Icons -->
                    <div class="mr-6 flex items-center gap-3 sm:gap-4">
                        <!-- WiFi Icon -->
                        <div class="relative" :class="headerTextClass">
                            <svg v-if="isOnline" class="-mt-1 h-6 w-6 sm:h-7 sm:w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21l-1.41-1.41A2 2 0 0112 18a2 2 0 011.41 1.59L12 21z" />
                                <path
                                    d="M12 17c-1.1 0-2.1.45-2.83 1.17l1.41 1.41c.78-.78 2.05-.78 2.83 0l1.41-1.41C14.1 17.45 13.1 17 12 17z"
                                    opacity="0.9"
                                />
                                <path
                                    d="M12 13c-2.21 0-4.21.9-5.66 2.34l1.41 1.41C8.79 15.71 10.3 15 12 15s3.21.71 4.24 1.76l1.41-1.41C16.21 13.9 14.21 13 12 13z"
                                    opacity="0.7"
                                />
                                <path
                                    d="M12 9c-3.31 0-6.31 1.35-8.49 3.51l1.41 1.41C6.66 12.28 9.18 11 12 11s5.34 1.28 7.07 3.51l1.41-1.41C18.31 10.35 15.31 9 12 9z"
                                    opacity="0.5"
                                />
                            </svg>
                            <svg v-else class="h-5 w-5 text-red-400 sm:h-6 sm:w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21l-1.41-1.41A2 2 0 0112 18a2 2 0 011.41 1.59L12 21z" />
                                <path d="M2.1 3.51L3.51 2.1l18.38 18.38-1.41 1.41L2.1 3.51z" />
                            </svg>
                        </div>

                        <!-- Pause Button -->
                        <button @click="togglePause" class="relative transition-opacity hover:opacity-80" :class="headerTextClass" title="Privacy Mode">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>

                        <!-- Options/Menu Button (Hamburger) -->
                        <button @click="openOptionsModal" class="transition-opacity hover:opacity-80" :class="headerTextClass" title="Options">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <!-- Note Icon -->
                        <button @click="toggleNotesDrawer" class="relative transition-opacity hover:opacity-80" :class="headerTextClass" title="Notes">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            <span
                                v-if="examNotes.length > 0"
                                class="absolute -top-3 -right-4 flex h-5 w-5 items-center justify-center rounded-full border-1 border-black bg-gray-600 text-[12px] font-bold text-white"
                            >
                                {{ examNotes.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Bottom Border Line -->
                <div class="h-px w-full bg-gray-300"></div>

                <!-- Options Modal -->
                <Teleport to="body">
                    <Transition name="fade">
                        <div
                            v-if="showOptionsModal"
                            class="fixed inset-0 z-[10000] flex items-start justify-center transition-colors duration-300"
                            :class="modalBgClass"
                            :style="modalTextSizeStyle"
                        >
                            <!-- Main Options View -->
                            <div v-if="optionsView === 'main'" class="w-full max-w-2xl px-4 py-6">
                                <!-- Header -->
                                <div class="mb-8 flex items-center justify-between">
                                    <div></div>
                                    <h2 class="text-xl font-semibold" :class="modalTextClass">Options</h2>
                                    <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                        &times;
                                    </button>
                                </div>

                                <!-- Menu Items -->
                                <div class="mx-auto max-w-md rounded-lg border" :class="modalBorderClass">
                                    <!-- Contrast -->
                                    <button
                                        @click="navigateToContrast"
                                        class="flex w-full items-center justify-between border-b px-4 py-4 transition-colors"
                                        :class="[modalTextClass, modalBorderClass, modalHoverClass]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="h-5 w-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                                <path stroke-width="2" d="M12 2v20" />
                                                <path d="M12 2a10 10 0 010 20" fill="currentColor" />
                                            </svg>
                                            <span>Contrast</span>
                                        </div>
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <!-- Text Size -->
                                    <button
                                        @click="navigateToTextSize"
                                        class="flex w-full items-center justify-between border-b px-4 py-4 transition-colors"
                                        :class="[modalTextClass, modalBorderClass, modalHoverClass]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="h-5 w-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="11" cy="11" r="8" stroke-width="2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" />
                                            </svg>
                                            <span>Text size</span>
                                        </div>
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    <!-- Test Instructions -->
                                    <button
                                        @click="navigateToInstructions"
                                        class="flex w-full items-center justify-between px-4 py-4 transition-colors"
                                        :class="[modalTextClass, modalHoverClass]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="h-5 w-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            <span>Test Instructions</span>
                                        </div>
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Contrast View -->
                            <div v-else-if="optionsView === 'contrast'" class="w-full max-w-3xl px-4 py-6">
                                <!-- Header -->
                                <div class="mb-8 flex items-center justify-between">
                                    <button @click="navigateBack" class="flex items-center gap-2 transition-opacity hover:opacity-70" :class="modalTextClass">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        <span>Options</span>
                                    </button>
                                    <h2 class="text-xl font-semibold" :class="modalTextClass">Contrast</h2>
                                    <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                        &times;
                                    </button>
                                </div>

                                <!-- Contrast Options -->
                                <div class="mx-auto max-w-md overflow-hidden rounded-lg border" :class="modalBorderClass">
                                    <!-- Black on White -->
                                    <button
                                        @click="setContrastTheme('black-on-white')"
                                        class="flex w-full items-center gap-3 border-b border-gray-200 bg-white px-4 py-4 text-gray-900"
                                    >
                                        <svg
                                            v-if="contrastTheme === 'black-on-white'"
                                            class="h-5 w-5 text-gray-900"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div v-else class="h-5 w-5"></div>
                                        <span>Black on white</span>
                                    </button>

                                    <!-- White on Black -->
                                    <button
                                        @click="setContrastTheme('white-on-black')"
                                        class="flex w-full items-center gap-3 border-b border-gray-200 bg-gray-900 px-4 py-4 text-white"
                                    >
                                        <svg v-if="contrastTheme === 'white-on-black'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div v-else class="h-5 w-5"></div>
                                        <span>White on black</span>
                                    </button>

                                    <!-- Yellow on Black -->
                                    <button
                                        @click="setContrastTheme('yellow-on-black')"
                                        class="flex w-full items-center gap-3 bg-gray-900 px-4 py-4 text-yellow-400"
                                    >
                                        <svg v-if="contrastTheme === 'yellow-on-black'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div v-else class="h-5 w-5"></div>
                                        <span>Yellow on black</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Text Size View -->
                            <div v-else-if="optionsView === 'textSize'" class="w-full max-w-2xl px-4 py-6">
                                <!-- Header -->
                                <div class="mb-8 flex items-center justify-between">
                                    <button @click="navigateBack" class="flex items-center gap-2 transition-opacity hover:opacity-70" :class="modalTextClass">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        <span>Options</span>
                                    </button>
                                    <h2 class="text-xl font-semibold" :class="modalTextClass">Text size</h2>
                                    <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                        &times;
                                    </button>
                                </div>

                                <!-- Text Size Options -->
                                <div class="mx-auto max-w-md overflow-hidden rounded-lg border" :class="modalBorderClass">
                                    <!-- Regular -->
                                    <button
                                        @click="setTextSize('regular')"
                                        class="flex w-full items-center gap-3 border-b px-4 py-4 transition-colors"
                                        :class="[
                                            modalTextClass,
                                            modalBorderClass,
                                            textSize === 'regular' ? (contrastTheme === 'black-on-white' ? 'bg-gray-100' : 'bg-gray-800') : '',
                                        ]"
                                    >
                                        <svg
                                            v-if="textSize === 'regular'"
                                            class="h-5 w-5"
                                            :class="modalTextClass"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div v-else class="h-5 w-5"></div>
                                        <span>Regular</span>
                                    </button>

                                    <!-- Large -->
                                    <button
                                        @click="setTextSize('large')"
                                        class="flex w-full items-center gap-3 border-b px-4 py-4 transition-colors"
                                        :class="[
                                            modalTextClass,
                                            modalBorderClass,
                                            textSize === 'large' ? (contrastTheme === 'black-on-white' ? 'bg-gray-100' : 'bg-gray-800') : '',
                                        ]"
                                    >
                                        <svg
                                            v-if="textSize === 'large'"
                                            class="h-5 w-5"
                                            :class="modalTextClass"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div v-else class="h-5 w-5"></div>
                                        <span>Large</span>
                                    </button>

                                    <!-- Extra Large -->
                                    <button
                                        @click="setTextSize('extra-large')"
                                        class="flex w-full items-center gap-3 px-4 py-4 transition-colors"
                                        :class="[
                                            modalTextClass,
                                            textSize === 'extra-large' ? (contrastTheme === 'black-on-white' ? 'bg-gray-100' : 'bg-gray-800') : '',
                                        ]"
                                    >
                                        <svg
                                            v-if="textSize === 'extra-large'"
                                            class="h-5 w-5"
                                            :class="modalTextClass"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div v-else class="h-5 w-5"></div>
                                        <span>Extra large</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Instructions View -->
                            <div v-else-if="optionsView === 'instructions'" class="w-full max-w-2xl px-4 py-6">
                                <!-- Header -->
                                <div class="mb-8 flex items-center justify-between">
                                    <button @click="navigateBack" class="flex items-center gap-2 transition-opacity hover:opacity-70" :class="modalTextClass">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        <span>Options</span>
                                    </button>
                                    <h2 class="text-xl font-semibold" :class="modalTextClass">Test Instructions</h2>
                                    <button @click="closeOptionsModal" class="text-2xl font-bold transition-opacity hover:opacity-70" :class="modalTextClass">
                                        &times;
                                    </button>
                                </div>

                                <!-- Instructions Content -->
                                <div class="mx-auto max-w-lg space-y-6 overflow-y-auto rounded-lg border p-6" :class="modalBorderClass" style="max-height: calc(100vh - 150px)">
                                    <!-- Test Tips -->
                                    <div class="space-y-3 text-sm" :class="modalTextClass">
                                        <h3 class="text-base font-bold uppercase">Test Tips</h3>
                                        <ul class="list-disc space-y-2 pl-5">
                                            <li><strong>Remaining Time:</strong> Timer is shown at the top. Manage your time properly.</li>
                                            <li><strong>Question Instructions:</strong> Each task has its own instructions. Follow the word limit carefully.</li>
                                            <li><strong>Navigation:</strong> Use TAB to move between fields.</li>
                                            <li><strong>Scroll Bar:</strong> Use scroll bar to view all content in the task.</li>
                                            <li><strong>Screen Settings:</strong> Click the top three-bar menu to change colour, contrast, or font size. Pause is available but timer continues.</li>
                                            <li><strong>Highlighting & Notes:</strong> You can highlight text and take notes for reference only.</li>
                                            <li><strong>Time End:</strong> When time finishes, answers will be submitted automatically.</li>
                                        </ul>
                                    </div>

                                    <!-- Instructions to Candidates -->
                                    <div class="space-y-3 text-sm" :class="modalTextClass">
                                        <h3 class="text-base font-bold uppercase">Instructions to Candidates</h3>
                                        <ul class="list-disc space-y-2 pl-5">
                                            <li>Time: {{ activeTask?.content.time }} minutes</li>
                                            <li>Write at least {{ activeTask?.content.words }} words.</li>
                                            <li>You can change your answers at any time during the test.</li>
                                        </ul>
                                    </div>

                                    <!-- Information -->
                                    <div class="space-y-3 text-sm" :class="modalTextClass">
                                        <h3 class="text-base font-bold uppercase">Information</h3>
                                        <ul class="list-disc space-y-2 pl-5">
                                            <li>This is {{ activeTaskCategory }} writing practice.</li>
                                            <li>Your response will be evaluated based on IELTS criteria.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </Teleport>

                <!-- Privacy Blur Overlay -->
                <Teleport to="body">
                    <Transition name="fade">
                        <div v-if="isPaused" class="fixed inset-0 z-[10000] flex items-center justify-center bg-white/70 backdrop-blur-[4px]">
                            <div class="text-center">
                                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full border-2 border-gray-900">
                                    <svg class="h-10 w-10 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                        />
                                    </svg>
                                </div>
                                <h2 class="mb-2 text-2xl font-bold text-gray-900">Privacy Mode</h2>
                                <p class="mb-2 text-gray-600">Screen is hidden for privacy</p>
                                <p class="mb-6 text-sm text-gray-500">Timer continues running</p>
                                <button
                                    @click="resumeTest"
                                    class="inline-flex items-center gap-2 border border-gray-900 bg-gray-900 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    Show Screen
                                </button>
                            </div>
                        </div>
                    </Transition>
                </Teleport>

                <!-- Notes Drawer -->
                <Teleport to="body">
                    <!-- Backdrop -->
                    <Transition name="fade">
                        <div v-if="showNotesDrawer" class="fixed inset-0 z-[9998] bg-black/50" @click="closeNotesDrawer"></div>
                    </Transition>

                    <!-- Drawer -->
                    <Transition name="slide">
                        <div
                            v-if="showNotesDrawer"
                            class="fixed top-0 right-0 z-[9999] flex h-full w-full flex-col overflow-hidden bg-white shadow-2xl sm:w-96"
                        >
                            <!-- Header -->
                            <div class="border-b border-gray-300 bg-white px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center bg-black">
                                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-black">My Notes</h3>
                                            <div class="text-xs text-gray-600">
                                                <span>Total: {{ examNotes.length }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        @click="closeNotesDrawer"
                                        class="flex h-8 w-8 items-center justify-center text-gray-500 transition-colors hover:bg-gray-100 hover:text-black"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                                <div v-if="examNotes.length === 0" class="flex h-full flex-col items-center justify-center py-12 text-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center bg-gray-100">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </div>
                                    <h4 class="mb-2 text-lg font-semibold text-black">No notes yet</h4>
                                    <p class="max-w-xs text-sm text-gray-600">
                                        Select text in the question area and click "Note" to add notes.
                                    </p>
                                </div>

                                <div v-else class="space-y-4">
                                    <div
                                        v-for="note in examNotes"
                                        :key="note.id"
                                        class="group relative border border-gray-300 bg-white p-4 transition-all hover:border-black hover:shadow-md"
                                    >
                                        <div class="mb-2 flex items-start justify-between gap-2">
                                            <span class="bg-black px-2.5 py-0.5 text-xs font-bold text-white">
                                                {{ note.part }}
                                            </span>
                                            <button
                                                @click.stop="deleteExamNote(note.id)"
                                                class="flex h-6 w-6 items-center justify-center bg-black text-white opacity-0 transition-all group-hover:opacity-100 hover:bg-gray-800"
                                                title="Delete note"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <!-- Selected Text (clickable to focus) -->
                                        <div
                                            @click="focusOnNote(note)"
                                            class="mb-2 cursor-pointer border border-gray-200 bg-gray-50 px-3 py-2 transition-colors hover:bg-gray-100"
                                        >
                                            <p class="mb-1 text-xs font-medium text-gray-500">Selected Text:</p>
                                            <p class="text-sm text-gray-700 italic">"{{ note.selectedText }}"</p>
                                        </div>
                                        <!-- Note Text -->
                                        <div
                                            @click="focusOnNote(note)"
                                            class="cursor-pointer border border-gray-200 bg-white px-3 py-2 transition-colors hover:bg-gray-50"
                                        >
                                            <p class="mb-1 text-xs font-medium text-gray-500">Your Note:</p>
                                            <p class="text-sm font-medium whitespace-pre-wrap text-black">{{ note.text }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </Teleport>
            </nav>

            <!-- Main Content -->
            <main class="flex-1 overflow-auto bg-gray-50 p-6">
                <!-- Results View -->
                <div v-if="examSubmitted" class="mx-auto max-w-6xl">
                    <!-- Comparison View (before AI evaluation) -->
                    <div v-if="showComparison && !aiEvaluationRequested" class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-gray-800">Review Your Writing</h2>
                            <p class="text-gray-500">{{ activeTaskCategory }} - {{ activeTask.name }}</p>
                        </div>

                        <!-- Two Column Layout -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <!-- Left Side: Question + Your Answer -->
                            <div class="space-y-4">
                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <h3 class="mb-2 font-semibold text-blue-800">Question</h3>
                                    <template v-if="activeTaskCategory === 'Task 1'">
                                        <p class="text-blue-700">{{ activeTask.content.description }}</p>
                                        <div v-if="activeTask.content.image" class="mt-3">
                                            <img :src="activeTask.content.image" alt="Task image" class="w-full rounded" />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p class="text-blue-700">{{ activeTask.content.topicStatement }}</p>
                                        <p class="mt-2 text-blue-700">{{ activeTask.content.question }}</p>
                                    </template>
                                </div>

                                <div class="rounded-lg border border-gray-300 bg-white p-4">
                                    <h3 class="mb-2 font-semibold text-gray-800">Your Answer ({{ wordCount }} words)</h3>
                                    <div class="max-h-96 overflow-y-auto rounded bg-gray-50 p-3">
                                        <p class="whitespace-pre-line text-gray-700">{{ userAnswer }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side: Sample Answers -->
                            <div class="space-y-4">
                                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                    <h3 class="mb-2 font-semibold text-green-800">Sample Answer 1 (Band 8+)</h3>
                                    <div class="max-h-[500px] overflow-y-auto rounded bg-white p-3">
                                        <p v-if="activeTask.content.sampleAnswer1" class="whitespace-pre-line text-sm text-gray-700">{{ activeTask.content.sampleAnswer1 }}</p>
                                        <p v-else class="text-sm italic text-gray-500">Sample answer not available.</p>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                                    <h3 class="mb-2 font-semibold text-purple-800">Sample Answer 2 (Band 8+)</h3>
                                    <div class="max-h-[500px] overflow-y-auto rounded bg-white p-3">
                                        <p v-if="activeTask.content.sampleAnswer2" class="whitespace-pre-line text-sm text-gray-700">{{ activeTask.content.sampleAnswer2 }}</p>
                                        <p v-else class="text-sm italic text-gray-500">Sample answer not available.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Get AI Feedback Button -->
                        <div class="flex flex-col items-center gap-4 pt-4">
                            <button @click="getAiFeedback" :disabled="isEvaluating" class="flex items-center gap-2 rounded-lg bg-blue-600 px-8 py-3 font-semibold text-white shadow-lg hover:bg-blue-700 disabled:opacity-50">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                Get AI Feedback
                            </button>
                            <p v-if="currentSubscription" class="text-sm text-gray-600">
                                <span class="font-medium text-blue-600">{{ currentSubscription.practice_writing_remaining || 0 }}</span> AI feedback remaining
                            </p>
                            <button @click="tryAnotherTask" class="text-sm text-gray-500 underline hover:text-gray-700">Try Another Task</button>
                        </div>
                    </div>

                    <!-- AI Evaluation Loading/Error/Results -->
                    <div v-else-if="isEvaluating" class="py-12 text-center">
                        <div class="mb-4 inline-block h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                        <p class="text-lg font-semibold text-gray-700">AI is evaluating your writing...</p>
                    </div>

                    <div v-else-if="evaluationError && aiEvaluationRequested" class="py-12 text-center">
                        <p class="text-lg font-semibold text-red-600">{{ evaluationError }}</p>
                        <button @click="tryAnotherTask" class="mt-4 rounded-md bg-gray-500 px-6 py-2 text-white">Try Again</button>
                    </div>

                    <div v-else-if="evaluationResult" class="space-y-6">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-gray-800">AI Evaluation Results</h2>
                            <div class="mt-4 inline-block rounded-full bg-gradient-to-r from-red-500 to-orange-500 p-1">
                                <div class="rounded-full bg-white px-8 py-4">
                                    <p class="text-sm text-gray-500 uppercase">Overall Band Score</p>
                                    <p class="text-5xl font-bold text-red-500">{{ overallBandScore }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-blue-50 p-4 text-center">
                                <p class="text-xs text-blue-600 uppercase">Task Achievement</p>
                                <p class="text-3xl font-bold text-blue-700">{{ activeTaskCategory === 'Task 1' ? evaluationResult.task1_ta : evaluationResult.task2_ta }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 p-4 text-center">
                                <p class="text-xs text-green-600 uppercase">Coherence</p>
                                <p class="text-3xl font-bold text-green-700">{{ activeTaskCategory === 'Task 1' ? evaluationResult.task1_cc : evaluationResult.task2_cc }}</p>
                            </div>
                            <div class="rounded-lg bg-purple-50 p-4 text-center">
                                <p class="text-xs text-purple-600 uppercase">Lexical Resource</p>
                                <p class="text-3xl font-bold text-purple-700">{{ activeTaskCategory === 'Task 1' ? evaluationResult.task1_lr : evaluationResult.task2_lr }}</p>
                            </div>
                            <div class="rounded-lg bg-orange-50 p-4 text-center">
                                <p class="text-xs text-orange-600 uppercase">Grammar</p>
                                <p class="text-3xl font-bold text-orange-700">{{ activeTaskCategory === 'Task 1' ? evaluationResult.task1_gra : evaluationResult.task2_gra }}</p>
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-4">
                            <h3 class="mb-2 font-semibold">Feedback</h3>
                            <p class="whitespace-pre-line text-gray-600">{{ activeTaskCategory === 'Task 1' ? evaluationResult.task1_feedback : evaluationResult.task2_feedback }}</p>
                        </div>

                        <div class="rounded-lg bg-yellow-50 p-4">
                            <h3 class="mb-2 font-semibold text-yellow-800">Teacher Feedback</h3>
                            <p class="whitespace-pre-line text-yellow-700">{{ evaluationResult.teacher_feedback }}</p>
                        </div>

                        <div class="text-center">
                            <button @click="tryAnotherTask" class="rounded-md bg-red-500 px-8 py-3 font-semibold text-white hover:bg-red-600">Try Another Task</button>
                        </div>
                    </div>
                </div>

                <!-- Writing Mode - IELTS002 Style Layout -->
                <div v-else class="flex h-[calc(100vh-120px)] flex-col pb-16">
                    <!-- Mobile: Stack vertically -->
                    <div class="flex flex-1 flex-col gap-4 overflow-hidden p-3 sm:gap-6 sm:px-4 lg:hidden">
                        <!-- Part Header Box (Mobile) -->
                        <div
                            ref="partHeaderRefMobile"
                            @mouseup="handleTextSelection"
                            @click="handleContentClick"
                            class="shrink-0 select-text border-gray-400 part-header-box px-4 py-3"
                            v-html="highlightedPartHeaderText"
                        ></div>

                        <!-- Question Section (Mobile) -->
                        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                            <div
                                ref="questionContentRefMobile"
                                @mouseup="handleTextSelection"
                                @click="handleContentClick"
                                class="noted-content space-y-3 select-text sm:space-y-4"
                            >
                                <div class="whitespace-pre-wrap font-bold text-black" v-html="highlightedQuestionText"></div>
                                <!-- Image for Task 1 -->
                                <div v-if="activeTaskCategory === 'Task 1' && activeTask?.content?.image" class="p-2 text-center sm:p-4">
                                    <img :src="activeTask.content.image" alt="Task image" class="mx-auto h-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Answer Section (Mobile) -->
                        <div class="shrink-0 px-4 py-2 sm:px-6">
                            <textarea
                                v-model="userAnswer"
                                class="h-[30vh] w-full resize-none border border-gray-400 p-3 text-black transition-all duration-200 focus:border-black focus:outline-none sm:h-[40vh] sm:p-4"
                                placeholder="Write your answer here..."
                                spellcheck="false"
                            ></textarea>
                            <div class="mt-2 flex justify-end">
                                <div class="text-sm font-medium text-black">
                                    Word count: <span class="font-bold">{{ wordCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop: Resizable Full-Height Layout -->
                    <div ref="containerRef" class="relative hidden h-full flex-1 lg:flex" :class="{ 'select-none': isResizing }">
                        <!-- Question Section (Desktop) -->
                        <div class="question-panel flex h-full flex-col overflow-hidden" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                            <!-- Part Header Box -->
                            <div
                                ref="partHeaderRefDesktop"
                                @mouseup="handleTextSelection"
                                @click="handleContentClick"
                                class="shrink-0 select-text border-gray-400 part-header-box px-4 py-3"
                                v-html="highlightedPartHeaderText"
                            ></div>

                            <!-- Question Content -->
                            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                                <div
                                    ref="questionContentRefDesktop"
                                    @mouseup="handleTextSelection"
                                    @click="handleContentClick"
                                    class="noted-content space-y-3 select-text sm:space-y-4"
                                >
                                    <div class="whitespace-pre-wrap font-bold text-black" v-html="highlightedQuestionText"></div>
                                    <!-- Image for Task 1 -->
                                    <div v-if="activeTaskCategory === 'Task 1' && activeTask?.content?.image" class="p-2 text-center sm:p-4">
                                        <img :src="activeTask.content.image" alt="Task image" class="mx-auto h-full" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resize Handle (Full Height) -->
                        <div
                            class="group relative flex h-full w-5 shrink-0 cursor-col-resize items-center justify-center border-x border-gray-200 bg-gray-50 transition-colors hover:bg-gray-100"
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

                        <!-- Answer Section (Desktop) -->
                        <div class="answer-panel flex h-full flex-col overflow-hidden p-4 sm:p-6" :style="{ '--panel-width': `${leftPanelWidth}%` }">
                            <textarea
                                v-model="userAnswer"
                                class="flex-1 w-full resize-none border border-gray-400 p-3 text-black transition-all duration-200 focus:border-black focus:outline-none sm:p-4"
                                placeholder="Write your answer here..."
                                spellcheck="false"
                            ></textarea>
                            <div class="mt-2 flex shrink-0 justify-end">
                                <div class="text-sm font-medium text-black">
                                    Word count: <span class="font-bold">{{ wordCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Selection Popup Tooltip - Speech Bubble Style -->
            <Teleport to="body">
                <div v-if="showSelectionPopup" class="pointer-events-none fixed inset-0 z-[10001]">
                    <div
                        class="highlight-tooltip pointer-events-auto fixed z-[10002]"
                        :style="{
                            left: `${selectionPopupPosition.x}px`,
                            top: `${selectionPopupPosition.y}px`,
                            transform: 'translateX(-50%)',
                        }"
                        @click.stop
                    >
                        <!-- Tooltip Content -->
                        <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                            <!-- Note Button -->
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
                            <!-- Highlight Button -->
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
                        <!-- Arrow pointing down -->
                        <div class="tooltip-arrow"></div>
                    </div>
                </div>
            </Teleport>

            <!-- Delete Highlight Tooltip -->
            <Teleport to="body">
                <div v-if="showDeleteTooltip" class="fixed inset-0 z-10001" @click="closeDeleteTooltip">
                    <div
                        class="delete-highlight-tooltip fixed z-10002"
                        :style="{
                            left: `${deleteTooltipPosition.x}px`,
                            top: `${deleteTooltipPosition.y}px`,
                            transform: 'translateX(-50%)',
                        }"
                    >
                        <!-- Arrow pointing UP -->
                        <div class="tooltip-arrow-up"></div>
                        <!-- Tooltip Content -->
                        <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                            <button
                                @click="deleteHighlightFromTooltip"
                                type="button"
                                class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100"
                            >
                                <!-- Trash/Bin icon -->
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
            </Teleport>

            <!-- Note Input Modal -->
            <Teleport to="body">
                <div
                    v-if="showNoteModal"
                    class="fixed z-[10003] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
                    :style="{
                        left: `${noteInputPosition.x}px`,
                        top: `${noteInputPosition.y}px`,
                        transform: 'translateX(-50%)',
                    }"
                    @click.stop
                >
                    <div class="mb-3">
                        <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">
                            "{{ selectedTextForNote }}"
                        </p>
                        <input
                            v-model="noteText"
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
                            class="border-2 border-gray-900 bg-white px-3 py-1 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100"
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

            <!-- Custom Footer (like IELTS002) -->
            <footer class="border-t border-gray-200 bg-gray-100">
                <div class="flex items-center justify-between px-6 py-3">
                    <!-- Brand -->
                    <div class="text-xl font-semibold">
                        <span class="text-black">English</span>
                        <span class="text-red-600"> Therapy</span>
                    </div>

                    <!-- Center: Submit/Cancel buttons -->
                    <div class="flex items-center gap-4">
                        <button
                            v-if="!examSubmitted"
                            @click="submitExam"
                            :disabled="wordCount < 50"
                            :class="[
                                'rounded-lg px-6 py-2 font-semibold transition-colors',
                                wordCount >= 50 ? 'bg-green-600 text-white hover:bg-green-700' : 'cursor-not-allowed bg-gray-300 text-gray-500',
                            ]"
                        >
                            Submit
                        </button>
                        <button @click="cancelExam" class="rounded-lg border border-gray-300 bg-white px-6 py-2 font-medium text-gray-700 hover:bg-gray-50">
                            {{ examSubmitted ? 'Exit' : 'Cancel' }}
                        </button>
                    </div>

                    <!-- Right: Time & Battery -->
                    <div class="flex items-center gap-5">
                        <span class="text-2xl font-bold tabular-nums text-gray-800">{{ currentTime }}</span>
                        <div class="flex items-center gap-1" :title="`Battery: ${batteryLevel}%`">
                            <svg class="h-7 w-7 text-gray-700" fill="currentColor" viewBox="0 0 24 24">
                                <rect x="2" y="7" width="18" height="10" rx="1" stroke="currentColor" stroke-width="1.5" fill="none" />
                                <rect x="20" y="10" width="2" height="4" rx="0.5" fill="currentColor" />
                                <rect x="3" y="8" :width="Math.max(1, (batteryLevel / 100) * 16)" height="8" :fill="batteryLevel > 20 ? 'currentColor' : '#ef4444'" />
                            </svg>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </Teleport>

    <!-- Normal View (Task Selection) - Hidden when exam started -->
    <template v-if="!examStarted">
        <!-- Access Blocked Warning -->
        <div v-if="currentAccessError" class="min-h-screen bg-gray-50 p-8">
            <div class="mx-auto max-w-4xl">
                <div class="overflow-hidden border-2 border-red-200 bg-white shadow-xl">
                    <div class="bg-gradient-to-r from-red-500 to-orange-500 px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Access Restricted</h1>
                                <p class="mt-1 text-red-100">Writing Practice Module</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="mb-6 rounded-xl border-2 border-red-200 bg-red-50 p-6">
                            <p class="text-red-800">{{ currentAccessError }}</p>
                        </div>
                        <div class="flex justify-center gap-4">
                            <Link href="/student/dashboard" class="rounded-lg bg-gray-900 px-6 py-3 font-semibold text-white">Go to Dashboard</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Normal Content -->
        <div v-else class="p-8">
            <!-- Task Category Selection -->
            <div class="mb-6 flex items-center">
                <div
                    :class="['mr-8 cursor-pointer border-b-2 pb-2 font-semibold', activeTaskCategory === 'Task 1' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-500 hover:text-red-500']"
                    @click="selectCategory('Task 1')"
                >
                    Task 1
                </div>
                <div
                    :class="['cursor-pointer border-b-2 pb-2 font-semibold', activeTaskCategory === 'Task 2' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-500 hover:text-red-500']"
                    @click="selectCategory('Task 2')"
                >
                    Task 2
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-md bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="font-semibold">Question Types</span>
                    <button
                        v-for="type in currentQuestionTypes"
                        :key="type"
                        @click="activeTaskCategory === 'Task 1' ? (activeTask1QuestionType = type) : (activeTask2QuestionType = type)"
                        :class="[
                            'rounded-full px-4 py-1 text-sm',
                            (activeTaskCategory === 'Task 1' && activeTask1QuestionType === type) || (activeTaskCategory === 'Task 2' && activeTask2QuestionType === type)
                                ? 'bg-red-500 text-white'
                                : 'bg-gray-200 hover:bg-gray-300',
                        ]"
                    >
                        {{ type }}
                    </button>
                </div>
                <div v-if="activeTaskCategory === 'Task 2'" class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold">Topic</span>
                    <button
                        v-for="topic in currentTopics"
                        :key="topic"
                        @click="activeTask2Topic = topic"
                        :class="['rounded-full px-4 py-1 text-sm', activeTask2Topic === topic ? 'bg-red-500 text-white' : 'bg-gray-200 hover:bg-gray-300']"
                    >
                        {{ topic }}
                    </button>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-12 gap-8">
                <!-- Sidebar -->
                <div class="col-span-3">
                    <div class="rounded-lg bg-white shadow-sm">
                        <ul>
                            <li
                                v-for="task in filteredTasks"
                                :key="task.id"
                                @click="selectTask(task)"
                                :class="['flex cursor-pointer items-center p-4', activeTask?.id === task.id ? 'bg-red-500 text-white' : 'hover:bg-gray-100']"
                            >
                                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="truncate">{{ task.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Content -->
                <div class="col-span-9">
                    <div v-if="activeTask" class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h1 class="text-2xl font-bold">{{ activeTaskCategory }} - {{ activeTask.name }}</h1>
                            <button @click="startExam" class="rounded-md bg-red-500 px-6 py-2 text-white hover:bg-red-600">Start</button>
                        </div>

                        <div v-if="activeTaskCategory === 'Task 1'">
                            <p class="mb-4">You should spend about {{ activeTask.content.time }} minutes on this task.</p>
                            <div class="mb-4 border-2 p-4">
                                <p class="font-semibold italic">{{ activeTask.content.description }}</p>
                            </div>
                            <p class="mb-6">Write at least {{ activeTask.content.words }} words.</p>
                            <div v-if="activeTask.content.image">
                                <img :src="activeTask.content.image" alt="Task image" class="mx-auto w-full max-w-2xl" />
                            </div>
                        </div>

                        <div v-else-if="activeTaskCategory === 'Task 2'">
                            <p class="mb-4">You should spend about {{ activeTask.content.time }} minutes on this task.</p>
                            <p class="mb-4">Write about the following topic:</p>
                            <div class="mb-4 border-2 p-4">
                                <p class="font-semibold italic">{{ activeTask.content.topicStatement }}</p>
                                <p class="mt-2 font-semibold italic">{{ activeTask.content.question }}</p>
                            </div>
                            <p class="mb-6">Write at least {{ activeTask.content.words }} words.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AppFooter />
    </template>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Notes Drawer Slide Transition */
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}

/* Part Header Box */
.part-header-box {
    background-color: #F1F2EC;
}

/* Text Selection */
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
.question-panel {
    width: 100%;
}

.answer-panel {
    width: 100%;
}

@media (min-width: 1024px) {
    .question-panel {
        width: calc(var(--panel-width) - 10px);
    }

    .answer-panel {
        width: calc(100% - var(--panel-width) - 10px);
    }
}

/* Full height layout helper */
.h-full-minus-header {
    height: calc(100vh - 120px);
}

/* Highlight colors */
:deep(mark[data-highlight-id]) {
    padding: 2px 0;
}

:deep(.highlight-yellow) {
    background-color: rgba(254, 240, 138, 0.9) !important;
}

:deep(.highlight-nocolor) {
    background-color: transparent;
}

/* Note highlight style */
:deep(.note-highlight) {
    background: rgba(253, 224, 71, 0.35);
    border-bottom: 1.5px solid #eab308;
    cursor: pointer;
    padding: 0 1px;
    border-radius: 2px;
}

/* Highlight Tooltip Styles */
:deep(.highlight-tooltip .tooltip-arrow) {
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

:deep(.highlight-tooltip .tooltip-arrow::before) {
    content: '';
    position: absolute;
    top: -9px;
    left: -8px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid #d1d5db;
    z-index: -1;
}

/* Delete Highlight Tooltip - Arrow pointing UP */
:deep(.delete-highlight-tooltip .tooltip-arrow-up) {
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

:deep(.delete-highlight-tooltip .tooltip-arrow-up::before) {
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
</style>
