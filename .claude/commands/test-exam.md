# IELTS Exam Testing Skill

When the user invokes this command with arguments like `test 1 listening` or `test 5 reading`, perform a comprehensive code review against the test cases below.

## Usage

```
/test-exam {testNumber} {section}
```

**Examples:**
- `/test-exam 1 listening` - Test IELTS001 Listening section
- `/test-exam 1 reading` - Test IELTS001 Reading section
- `/test-exam 5 listening` - Test IELTS005 Listening section
- `/test-exam 22 reading` - Test IELTS022 Reading section

## Test Number to Folder Mapping

| Test # | Folder |
|--------|--------|
| 1 | IELTS001 |
| 2 | IELTS002 |
| 3 | IELTS003 |
| 4 | IELTS004 |
| 5 | IELTS005 |
| 6 | IELTS006 |
| 7 | IELTS007 |
| 8 | IELTS008 |
| 9 | IELTS009 |
| 10 | IELTS010 |
| 11 | IELTS011 |
| 12 | IELTS012 |
| 13 | IELTS013 |
| 14 | IELTS014 |
| 15 | IELTS015 |
| 16 | IELTS016 |
| 17 | IELTS017 |
| 18 | IELTS018 |
| 19 | IELTS019 |
| 20 | IELTS020 |
| 21 | IELTS021 |
| 22 | IELTS022 |
| 23 | IELTS023 |
| 24 | IELTS024 |
| 25 | IELTS025 |
| 26 | IELTS026 |

**File Path Pattern:**
- Section file: `resources/js/pages/Exam/IELTS{XXX}/Sections/{Section}.vue`
- Example: `resources/js/pages/Exam/IELTS001/Sections/Listening.vue`

## Test Cases

### TC-01: Verify 40 Questions
**Description:** Verify that the exam contains exactly 40 questions.

**What to Check:**
- [ ] `correctAnswers` object exists and has keys from 1 to 40
- [ ] All 40 keys are present (no missing questions)
- [ ] `compareAnswers` function loops through 1-40
- [ ] `total_questions: 40` in submit payload

**Pass Criteria:** All 40 question keys exist in correctAnswers

---

### TC-02: Line Highlighting
**Description:** Verify that each line of the passage can be highlighted properly.

**What to Check:**
- [ ] Part components have `select-text` class on highlightable areas
- [ ] `useHighlight` composable is imported (or highlight logic exists)
- [ ] `handleFocusNote` function handles highlighting
- [ ] Highlight classes exist: `highlight-yellow`, `highlight-green`, `highlight-blue`, `highlight-pink`, `highlight-orange`
- [ ] `mark` elements with `data-highlight-id` attribute

**Pass Criteria:** Highlighting infrastructure is properly implemented

---

### TC-03: Note Adding
**Description:** Verify that users can add notes to any line of the passage.

**What to Check:**
- [ ] `notes` ref exists in Part components
- [ ] `saveNote` or equivalent function exists
- [ ] Notes include `part` identifier (e.g., 'Part 1', 'Part 2')
- [ ] Part components expose `notes` via `defineExpose`
- [ ] Parent component tracks notes: `part1Notes`, `part2Notes`, etc.

**Pass Criteria:** Note adding functionality is properly wired

---

### TC-04: Note Navigation
**Description:** After adding a note, verify that clicking the note from the note box navigates to and highlights the correct line.

**What to Check:**
- [ ] `handleFocusNote` function exists in parent Section file
- [ ] Function switches to correct part based on `note.part`
- [ ] Uses `nextTick()` before DOM operations
- [ ] Searches for existing highlights with `mark[data-highlight-id]`
- [ ] Falls back to text search in `.select-text` areas
- [ ] Scrolls to found element with `scrollIntoView`
- [ ] Adds temporary visual indicator (ring, highlight)
- [ ] `@focus-note` event is emitted from Header

**Pass Criteria:** Clicking note navigates and highlights correctly

---

### TC-05: Flag Feature
**Description:** Verify that the flag feature works for every line or question.

**What to Check:**
- [ ] `flaggedQuestions` ref exists as `Set<number>`
- [ ] `handleToggleFlag` function toggles flag state
- [ ] `@toggle-flag` event is passed to Part components
- [ ] `:flagged-questions` prop is passed to Part components
- [ ] Part components receive and use flagged state
- [ ] Flag visual indicator (bookmark icon, color change)

**Pass Criteria:** Flag toggle works for all questions 1-40

---

### TC-06: Multi-line Highlighting
**Description:** Verify that multi-line highlighting works correctly.

**What to Check:**
- [ ] Highlight composable handles text selection across nodes
- [ ] `TreeWalker` or similar DOM traversal for multi-node selection
- [ ] `Range` API usage for selection handling
- [ ] Highlight persists across line breaks
- [ ] Style scoped CSS handles highlight marks in all themes

**Pass Criteria:** Can highlight text spanning multiple lines/paragraphs

---

### TC-07: Answer Saving
**Description:** Verify that all selected answers are saved and function correctly.

**What to Check:**
- [ ] `answers` ref exists in each Part component
- [ ] `getAnswers()` method is exposed via `defineExpose`
- [ ] Parent collects answers from all parts before submit
- [ ] Answer format: `{ q1: 'answer', q2: 'answer', ... }`
- [ ] `updateAnsweredQuestions` tracks filled answers
- [ ] `answeredQuestions` Set updates correctly
- [ ] Submit payload includes all `user_answers`

**Pass Criteria:** All answers are collected and submitted

---

## Output Format

After testing, provide a report in this format:

```
## Test Report: IELTS{XXX} {Section}

### Summary
- Total Test Cases: 7
- Passed: X
- Failed: X
- Warnings: X

### Results

| TC | Test Case | Status | Notes |
|----|-----------|--------|-------|
| 01 | 40 Questions | PASS/FAIL | details |
| 02 | Line Highlighting | PASS/FAIL | details |
| 03 | Note Adding | PASS/FAIL | details |
| 04 | Note Navigation | PASS/FAIL | details |
| 05 | Flag Feature | PASS/FAIL | details |
| 06 | Multi-line Highlight | PASS/FAIL | details |
| 07 | Answer Saving | PASS/FAIL | details |

### Issues Found
- Issue 1: description
- Issue 2: description

### Recommendations
- Recommendation 1
- Recommendation 2
```

## Instructions for Claude

1. Parse the test number and section from user input
2. Map test number to IELTS folder (pad with zeros: 1 → 001)
3. Read the Section file (Listening.vue or Reading.vue)
4. Read associated Part components if needed
5. Check each test case systematically
6. Generate the test report
7. Highlight any critical issues

## Notes

- For Listening: Parts 1-4, Questions typically split as 1-10, 11-20, 21-30, 31-40
- For Reading: Parts 1-3, Questions typically split as 1-13, 14-26, 27-40
- Always check both the Section file AND the Part component files
- Part components are in `resources/js/components/Exam/` directory
