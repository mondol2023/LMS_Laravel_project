import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

export interface UseReadingTestPageOptions {
    slug: string;
    examId: string;
    testNumber: number;
    correctAnswers: Record<number, string>;
    readingPart1Ref: Ref<any>;
    readingPart2Ref: Ref<any>;
    readingPart3Ref: Ref<any>;
    userPhone?: string | null;
}

export function useReadingTestPage(options: UseReadingTestPageOptions) {
    const { slug, examId, testNumber, correctAnswers, readingPart1Ref, readingPart2Ref, readingPart3Ref } = options;

    const { normalize, calculateBandScore, compareAnswers: scoringCompareAnswers } = useIeltsScoring();

    // ================================
    // Component state
    // ================================
    const activePart = ref<'part1' | 'part2' | 'part3'>('part1');
    const showSubmitModal = ref(false);
    const showReviewModal = ref(false);
    const answeredQuestions = ref<Set<number>>(new Set());
    const isTimeUp = ref(false);
    const showForceSubmitModal = ref(false);
    const isLeavingPage = ref(false);

    // Notes from each part
    const part1Notes = computed(() => readingPart1Ref.value?.notes || []);
    const part2Notes = computed(() => readingPart2Ref.value?.notes || []);
    const part3Notes = computed(() => readingPart3Ref.value?.notes || []);

    // ================================
    // Navigation
    // ================================
    const handleNavigate = (part: 'part1' | 'part2' | 'part3') => {
        activePart.value = part;
    };

    const handleScrollToQuestion = async (questionNumber: number) => {
        if (questionNumber <= 13) activePart.value = 'part1';
        else if (questionNumber <= 26) activePart.value = 'part2';
        else activePart.value = 'part3';

        await nextTick();

        if (questionNumber <= 13) readingPart1Ref.value?.scrollToQuestion(questionNumber);
        else if (questionNumber <= 26) readingPart2Ref.value?.scrollToQuestion(questionNumber);
        else readingPart3Ref.value?.scrollToQuestion(questionNumber);
    };

    // ================================
    // Modal handlers
    // ================================
    const handleShowSubmitModal = () => {
        showSubmitModal.value = true;
    };

    const handleCancelSubmit = () => {
        if (isTimeUp.value) return;
        showSubmitModal.value = false;
    };

    const handleShowReviewModal = () => {
        if (isTimeUp.value) return;
        showReviewModal.value = true;
    };

    const handleCloseReviewModal = () => {
        showReviewModal.value = false;
    };

    const handleTimeUp = () => {
        isTimeUp.value = true;
        showSubmitModal.value = true;
    };

    // ================================
    // Get all user answers
    // ================================
    const getAllUserAnswers = () => {
        return {
            ...(readingPart1Ref.value?.getAnswers?.() || {}),
            ...(readingPart2Ref.value?.getAnswers?.() || {}),
            ...(readingPart3Ref.value?.getAnswers?.() || {}),
        };
    };

    // ================================
    // Answer comparison using inline normalize (matching existing behavior)
    // ================================
    const normalizeAnswer = (str: string): string => {
        let normalized = str.toString().trim().toLowerCase();

        normalized = normalized.replace(/\b(\d+)(st|nd|rd|th)\b/g, '$1');

        normalized = normalized
            .replace(/[.,!?;:'"()-]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        normalized = normalized
            .replace(/\b(\d+)\s*cm\b/g, '$1 centimeter')
            .replace(/\b(\d+)\s*centimeters?\b/g, '$1 centimeter')
            .replace(/\b(\d+)\s*mm\b/g, '$1 millimeter')
            .replace(/\b(\d+)\s*millimeters?\b/g, '$1 millimeter')
            .replace(/\b(\d+)\s*m\b/g, '$1 meter')
            .replace(/\b(\d+)\s*meters?\b/g, '$1 meter')
            .replace(/\b(\d+)\s*%\b/g, '$1 percent');

        normalized = normalized
            .replace(/\bfemales?\b/g, 'women')
            .replace(/\bmales?\b/g, 'men')
            .replace(/\bchildren?\b/g, 'child')
            .replace(/\bpeoples?\b/g, 'people');

        normalized = normalized
            .replace(/\b(yes|y|true|correct)\b/g, 'yes')
            .replace(/\b(no|n|false|incorrect)\b/g, 'no')
            .replace(/\b(not given|not stated|not mentioned|ng)\b/g, 'not given');

        return normalized.trim();
    };

    const localCalculateBandScore = (score: number): number =>
        score >= 39
            ? 9
            : score >= 37
              ? 8.5
              : score >= 35
                ? 8
                : score >= 33
                  ? 7.5
                  : score >= 30
                    ? 7
                    : score >= 27
                      ? 6.5
                      : score >= 23
                        ? 6
                        : score >= 19
                          ? 5.5
                          : score >= 15
                            ? 5
                            : score >= 13
                              ? 4.5
                              : score >= 10
                                ? 4
                                : score >= 8
                                  ? 3.5
                                  : score >= 6
                                    ? 3
                                    : score >= 4
                                      ? 2.5
                                      : 0;

    const compareAnswers = (userAnswers: Record<string, string>) => {
        let correct = 0;
        for (let i = 1; i <= 40; i++) {
            const userAnswer = normalizeAnswer(userAnswers[`q${i}`] || '');
            const correctAnswer = normalizeAnswer(correctAnswers[i]);

            const possibleAnswers = correctAnswer.split('/').map((a) => normalizeAnswer(a.trim()));

            const isCorrect = possibleAnswers.some((possibleAnswer) => {
                if (possibleAnswer === userAnswer) return true;

                if (possibleAnswer.includes(' ') && userAnswer.includes(' ')) {
                    const possibleWords = possibleAnswer.split(' ');
                    const userWords = userAnswer.split(' ');
                    return possibleWords.every((word) => userWords.includes(word)) || userWords.every((word) => possibleWords.includes(word));
                }

                return false;
            });

            if (isCorrect) correct++;
        }
        return { correct, incorrect: 40 - correct };
    };

    // ================================
    // Submit logic
    // ================================
    const submitTest = async () => {
        isLeavingPage.value = true;

        try {
            const allAnswers = getAllUserAnswers();
            const { correct, incorrect } = compareAnswers(allAnswers);
            const bandScore = localCalculateBandScore(correct);

            sessionStorage.removeItem(`reading_visited_${examId}`);

            const formattedUserAnswers: Record<number, string> = {};
            for (let i = 1; i <= 40; i++) {
                formattedUserAnswers[i] = allAnswers[`q${i}`] || '';
            }

            router.post(`/practice/results/reading/${testNumber}`, {
                userAnswers: formattedUserAnswers,
                correctAnswers: correctAnswers,
                score: {
                    correct,
                    incorrect,
                    bandScore,
                },
            });
        } catch (err) {
            console.error('Submission error:', err);
            router.visit(`/exam/${slug}`);
        }
    };

    const handleConfirmSubmit = async () => {
        showSubmitModal.value = false;
        await submitTest();
    };

    const handleForceSubmit = async () => {
        showForceSubmitModal.value = false;
        await submitTest();
    };

    // ================================
    // Note handlers
    // ================================
    const handleDeleteNote = ({ id, part }: { id: number; part: string }) => {
        if (part === 'Passage 1') {
            readingPart1Ref.value?.deleteNote(id);
        } else if (part === 'Passage 2') {
            readingPart2Ref.value?.deleteNote(id);
        } else if (part === 'Passage 3') {
            readingPart3Ref.value?.deleteNote(id);
        }
    };

    const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
        if (note.part === 'Passage 1') activePart.value = 'part1';
        else if (note.part === 'Passage 2') activePart.value = 'part2';
        else if (note.part === 'Passage 3') activePart.value = 'part3';

        await nextTick();

        setTimeout(() => {
            const marks = document.querySelectorAll('mark[data-highlight-id]');
            let foundMark: Element | null = null;

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
                }, 2500);
            } else {
                const contentAreas = document.querySelectorAll('.select-text, .text-gray-700, .text-gray-800, .text-black, .text-zinc-900');
                let foundText = false;

                for (const contentArea of contentAreas) {
                    if (foundText) break;

                    const elementText = contentArea.textContent || '';
                    if (!elementText.includes(note.selectedText)) {
                        continue;
                    }

                    const walker = document.createTreeWalker(contentArea, NodeFilter.SHOW_TEXT, null);

                    let node;
                    while ((node = walker.nextNode())) {
                        const text = node.textContent || '';
                        const index = text.indexOf(note.selectedText);

                        if (index !== -1 && node.parentElement) {
                            const range = document.createRange();
                            range.setStart(node, index);
                            range.setEnd(node, index + note.selectedText.length);

                            const tempMark = document.createElement('mark');
                            tempMark.className = 'bg-blue-200 ring-4 ring-blue-400 ring-offset-2 transition-all px-1 rounded';

                            try {
                                range.surroundContents(tempMark);

                                tempMark.scrollIntoView({ behavior: 'smooth', block: 'center' });

                                setTimeout(() => {
                                    const parent = tempMark.parentNode;
                                    if (parent) {
                                        parent.replaceChild(document.createTextNode(note.selectedText), tempMark);
                                        parent.normalize();
                                    }
                                }, 2500);

                                foundText = true;
                                break;
                            } catch (e) {
                                console.warn('Failed to surround contents, trying alternative approach:', e);
                                continue;
                            }
                        }
                    }
                }
            }
        }, 100);
    };

    // ================================
    // Track answered questions
    // ================================
    const updateAnsweredQuestions = () => {
        const allAnswers = getAllUserAnswers();
        const newAnsweredQuestions = new Set<number>();

        for (let i = 1; i <= 40; i++) {
            const answer = allAnswers[`q${i}`];
            if (answer && answer.toString().trim() !== '') {
                newAnsweredQuestions.add(i);
            }
        }

        answeredQuestions.value = newAnsweredQuestions;
    };

    // ================================
    // Browser control logic
    // ================================
    const handleBeforeUnload = (event: BeforeUnloadEvent) => {
        if (!isLeavingPage.value) {
            event.preventDefault();
            event.returnValue = '';
            return event.returnValue;
        }
    };

    const handleVisibilityChange = () => {
        if (document.hidden && !isLeavingPage.value) {
            showForceSubmitModal.value = true;
        }
    };

    const preventBackNavigation = () => {
        if (!isLeavingPage.value) {
            window.history.pushState(null, '', window.location.href);
            showForceSubmitModal.value = true;
        }
    };

    const handleMouseButton = (event: MouseEvent) => {
        if (isLeavingPage.value) return;

        if (event.button === 3 || event.button === 4) {
            event.preventDefault();
            event.stopPropagation();
            showForceSubmitModal.value = true;
            return false;
        }
    };

    const handleContextMenu = (event: MouseEvent) => {
        if (!isLeavingPage.value) {
            event.preventDefault();
            return false;
        }
    };

    // ================================
    // Lifecycle
    // ================================
    let answerCheckInterval: any = null;

    onMounted(() => {
        const hasVisitedReading = sessionStorage.getItem(`reading_visited_${examId}`);

        if (hasVisitedReading === 'true') {
            showForceSubmitModal.value = true;
        } else {
            sessionStorage.setItem(`reading_visited_${examId}`, 'true');
        }

        answerCheckInterval = setInterval(() => {
            updateAnsweredQuestions();
        }, 500);

        window.addEventListener('beforeunload', handleBeforeUnload);
        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('popstate', preventBackNavigation);
        document.addEventListener('mousedown', handleMouseButton, true);
        document.addEventListener('mouseup', handleMouseButton, true);
        document.addEventListener('contextmenu', handleContextMenu);

        window.history.pushState(null, '', window.location.href);

        (window as any).__historyInterval = setInterval(() => {
            if (!isLeavingPage.value) {
                window.history.pushState(null, '', window.location.href);
            }
        }, 100);
    });

    onBeforeUnmount(() => {
        if (answerCheckInterval) {
            clearInterval(answerCheckInterval);
        }

        window.removeEventListener('beforeunload', handleBeforeUnload);
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('popstate', preventBackNavigation);
        document.removeEventListener('mousedown', handleMouseButton, true);
        document.removeEventListener('mouseup', handleMouseButton, true);
        document.removeEventListener('contextmenu', handleContextMenu);

        if ((window as any).__historyInterval) {
            clearInterval((window as any).__historyInterval);
        }
    });

    return {
        // State
        activePart,
        showSubmitModal,
        showReviewModal,
        showForceSubmitModal,
        answeredQuestions,
        isTimeUp,
        isLeavingPage,
        part1Notes,
        part2Notes,
        part3Notes,

        // Navigation
        handleNavigate,
        handleScrollToQuestion,

        // Modals
        handleShowSubmitModal,
        handleCancelSubmit,
        handleShowReviewModal,
        handleCloseReviewModal,
        handleTimeUp,

        // Submit
        handleConfirmSubmit,
        handleForceSubmit,

        // Answers
        getAllUserAnswers,

        // Notes
        handleDeleteNote,
        handleFocusNote,
    };
}
