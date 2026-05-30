# IELTS Reading Component Transfer Guide

> **Purpose:** Step-by-step instructions for creating new IELTS Reading test versions (e.g., `ReadingIELTS016`, `ReadingIELTS017`, etc.) from an existing version.

---

## Table of Contents

1. [Directory Structure](#1-directory-structure)
2. [Files Overview](#2-files-overview)
3. [Transfer Process](#3-transfer-process)
4. [File-by-File Changes](#4-file-by-file-changes)
5. [Content Updates for Part Components](#5-content-updates-for-part-components)
6. [Question Type Templates](#6-question-type-templates)
7. [Segment ID System](#7-segment-id-system)
8. [Page Integration](#8-page-integration)
9. [Checklist](#9-checklist)
10. [Quick Reference](#10-quick-reference)

---

## 1. Directory Structure

### Source Location
```
resources/js/components/Exam/Reading/           # Original template
resources/js/components/Exam/ReadingIELTS002/   # Reference version (recommended source)
```

### Target Location
```
resources/js/components/Exam/ReadingIELTSXXX/   # Replace XXX with your test number (e.g., 016)
```

---

## 2. Files Overview

Each Reading test version contains **6 files**:

| File | Purpose |
|------|---------|
| `ReadingHeaderIELTSXXX.vue` | Timer, notes drawer, font/brightness controls, submit button |
| `ReadingFooterIELTSXXX.vue` | Part navigation tabs, question number grid |
| `ReadingPart1IELTSXXX.vue` | Passage 1 content + Questions (typically 1-13 or 1-14) |
| `ReadingPart2IELTSXXX.vue` | Passage 2 content + Questions (typically 14-26 or 15-27) |
| `ReadingPart3IELTSXXX.vue` | Passage 3 content + Questions (typically 27-40 or 28-40) |
| `ReadingReviewModalIELTSXXX.vue` | Answer review modal before submission |

---

## 3. Transfer Process

### Step 1: Create Directory
```bash
mkdir -p resources/js/components/Exam/ReadingIELTSXXX
```

### Step 2: Copy Files from Source
```bash
cp resources/js/components/Exam/ReadingIELTS002/*.vue resources/js/components/Exam/ReadingIELTSXXX/
```

### Step 3: Rename All Files
```bash
cd resources/js/components/Exam/ReadingIELTSXXX

# Rename each file
mv ReadingHeaderIELTS002.vue ReadingHeaderIELTSXXX.vue
mv ReadingFooterIELTS002.vue ReadingFooterIELTSXXX.vue
mv ReadingPart1IELTS002.vue ReadingPart1IELTSXXX.vue
mv ReadingPart2IELTS002.vue ReadingPart2IELTSXXX.vue
mv ReadingPart3IELTS002.vue ReadingPart3IELTSXXX.vue
mv ReadingReviewModalIELTS002.vue ReadingReviewModalIELTSXXX.vue
```

### Step 4: Global Find & Replace (All Files)

Run these replacements in **ALL 6 files**:

| Find | Replace |
|------|---------|
| `IELTS002` | `IELTSXXX` |
| `ielts002` | `ieltsxxx` |

---

## 4. File-by-File Changes

### 4.1 ReadingHeaderIELTSXXX.vue

**Changes Required:** Only suffix replacements (Step 4 above)

**No content changes needed** - Header is identical across all tests.

---

### 4.2 ReadingFooterIELTSXXX.vue

**Changes Required:** Only suffix replacements (Step 4 above)

**No content changes needed** - Footer is identical across all tests.

---

### 4.3 ReadingReviewModalIELTSXXX.vue

**Changes Required:** Only suffix replacements (Step 4 above)

**No content changes needed** - Review modal is identical across all tests.

---

### 4.4 ReadingPart1IELTSXXX.vue (MAJOR CHANGES)

#### A. Update LocalStorage Key (Line ~20)
```typescript
// FIND:
const PANEL_WIDTH_KEY = 'reading-ielts002-part1-panel-width';

// REPLACE WITH:
const PANEL_WIDTH_KEY = 'reading-ieltsxxx-part1-panel-width';
```

#### B. Update Passage Text (Line ~56-71)
```typescript
// REPLACE entire passageText content:
const passageText = ref(`
YOUR NEW PASSAGE TEXT HERE.

Use double line breaks for paragraph separation.

Each paragraph should flow naturally.
`);
```

#### C. Update Answers Object (Line ~38-53)
Adjust the number of question keys to match your test:
```typescript
const answers = ref({
    q1: '',
    q2: '',
    q3: '',
    // ... continue for all questions in Part 1
    q14: '',  // Last question number for Part 1
});
```

#### D. Update allStaticTexts Array (Line ~74-110)
This array contains ALL highlightable text in order of appearance:
```typescript
const allStaticTexts = [
    // Index 0-3: Header section
    "READING PASSAGE 1",
    "You should spend about 20 minutes on Question 1-14, which are based on Reading passage 1 below.",
    "Your Passage Title",
    "Your Passage Subtitle (if any)",

    // Index 4: Main passage (references passageText.value)
    passageText.value,

    // Index 5-6: Questions section header
    "QUESTIONS",
    "Answer all questions based on Reading Passage 1",

    // Index 7+: Question group headers and question texts
    "Questions 1-10",
    "Reading Passage 1 has 8 paragraphs labelled A-H.",
    "Which paragraph contains the following information?",
    // ... all question texts
];
```

#### E. Update Template Question Structure
Modify the `<template>` section to match your question types and count.

---

### 4.5 ReadingPart2IELTSXXX.vue (MAJOR CHANGES)

Same process as Part 1, but:
- LocalStorage key: `reading-ieltsxxx-part2-panel-width`
- Question numbers typically start from 14 or 15
- Different passage and questions

---

### 4.6 ReadingPart3IELTSXXX.vue (MAJOR CHANGES)

Same process as Part 1, but:
- LocalStorage key: `reading-ieltsxxx-part3-panel-width`
- Question numbers typically start from 27 or 28
- Different passage and questions

---

## 5. Content Updates for Part Components

### 5.1 Passage Text Format

```typescript
const passageText = ref(`Paragraph A
First paragraph content here. Can span multiple sentences.

Paragraph B
Second paragraph content here.

Paragraph C
Third paragraph content. Use labeled paragraphs (A, B, C...) for matching questions.
`);
```

**Important:**
- Use `\n\n` (double newline) for paragraph breaks
- Label paragraphs if questions reference them (A, B, C, etc.)
- Keep HTML minimal (avoid `<br/>` unless necessary)

---

### 5.2 Answers Object Pattern

```typescript
// For Part 1 (Questions 1-14)
const answers = ref({
    q1: '', q2: '', q3: '', q4: '', q5: '',
    q6: '', q7: '', q8: '', q9: '', q10: '',
    q11: '', q12: '', q13: '', q14: '',
});

// For Part 2 (Questions 15-27)
const answers = ref({
    q15: '', q16: '', q17: '', q18: '', q19: '',
    q20: '', q21: '', q22: '', q23: '', q24: '',
    q25: '', q26: '', q27: '',
});

// For Part 3 (Questions 28-40)
const answers = ref({
    q28: '', q29: '', q30: '', q31: '', q32: '',
    q33: '', q34: '', q35: '', q36: '', q37: '',
    q38: '', q39: '', q40: '',
});
```

---

## 6. Question Type Templates

### 6.1 Paragraph Matching (Select A-H)

```vue
<div id="question-1" class="">
    <div class="flex items-start gap-4">
        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center mt-3 bg-black border border-gray-900">
            <span class="text-sm font-bold text-white">1</span>
        </div>
        <div class="grid grid-cols-12 items-center gap-2">
            <div class="col-span-3">
                <select v-model="answers.q1"
                    class="w-full border-2 border-gray-900 px-3 py-1 text-base transition-colors focus:border-black focus:outline-none sm:text-lg">
                    <option value="">Select</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                    <option value="F">F</option>
                    <option value="G">G</option>
                    <option value="H">H</option>
                </select>
            </div>
            <div class="col-span-9">
                <p class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg">
                    <span class="font-semibold select-text" data-segment-id="segment_12"
                        v-html="getHighlightedSegment(allStaticTexts[12])"></span>
                </p>
            </div>
        </div>
    </div>
</div>
```

---

### 6.2 TRUE/FALSE/NOT GIVEN

```vue
<div id="question-4" class="">
    <div class="flex items-start gap-4">
        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center mt-3 bg-black border border-gray-900">
            <span class="text-sm font-bold text-white">4</span>
        </div>
        <div class="grid grid-cols-12 items-center gap-2">
            <div class="col-span-3">
                <select v-model="answers.q4"
                    class="w-full border-2 border-gray-900 px-3 py-1 text-base transition-colors focus:border-black focus:outline-none sm:text-lg">
                    <option value="">Select</option>
                    <option value="TRUE">TRUE</option>
                    <option value="FALSE">FALSE</option>
                    <option value="NOT GIVEN">NOT GIVEN</option>
                </select>
            </div>
            <div class="col-span-9">
                <p class="text-base leading-relaxed font-medium text-gray-700 sm:text-lg">
                    <span class="font-semibold select-text" data-segment-id="segment_X"
                        v-html="getHighlightedSegment(allStaticTexts[X])"></span>
                </p>
            </div>
        </div>
    </div>
</div>
```

---

### 6.3 YES/NO/NOT GIVEN

```vue
<select v-model="answers.qX"
    class="w-full border-2 border-gray-900 px-3 py-1 text-base transition-colors focus:border-black focus:outline-none sm:text-lg">
    <option value="">Select</option>
    <option value="YES">YES</option>
    <option value="NO">NO</option>
    <option value="NOT GIVEN">NOT GIVEN</option>
</select>
```

---

### 6.4 Fill-in-the-Blank (Text Input)

```vue
<p id="question-11">
    <span class="select-text" data-segment-id="segment_27"
        v-html="getHighlightedSegment(allStaticTexts[27])"></span>

    <span class="mx-2 inline-flex items-center space-x-2">
        <span class="inline-flex h-8 w-8 items-center justify-center bg-black border border-gray-900 text-sm font-bold text-white">11</span>
        <input v-model="answers.q11" type="text"
            class="w-36 border-2 border-gray-900 px-3 py-1 text-center text-base font-medium transition-colors focus:border-black focus:outline-none sm:text-lg"
            placeholder="____" />
    </span>

    <span class="select-text" data-segment-id="segment_28"
        v-html="getHighlightedSegment(allStaticTexts[28])"></span>
</p>
```

---

### 6.5 Multiple Choice (A/B/C/D)

```vue
<select v-model="answers.qX"
    class="w-full border-2 border-gray-900 px-3 py-1 text-base transition-colors focus:border-black focus:outline-none sm:text-lg">
    <option value="">Select</option>
    <option value="A">A</option>
    <option value="B">B</option>
    <option value="C">C</option>
    <option value="D">D</option>
</select>
```

---

## 7. Segment ID System

### How It Works

The highlight system uses `data-segment-id` attributes to track text positions:

```vue
<span data-segment-id="segment_0" v-html="getHighlightedSegment(allStaticTexts[0])"></span>
<span data-segment-id="segment_1" v-html="getHighlightedSegment(allStaticTexts[1])"></span>
<span data-segment-id="segment_4" v-html="getHighlightedSegment(allStaticTexts[4])"></span>
```

### Rules

1. **Index must match:** `segment_X` must reference `allStaticTexts[X]`
2. **Sequential order:** Segments should appear in document order
3. **Unique IDs:** Each segment ID must be unique within the component
4. **Passage text:** Main passage is typically `segment_4` (index 4 in array)

### Example Mapping

```
allStaticTexts[0]  = "READING PASSAGE 1"           -> segment_0
allStaticTexts[1]  = "You should spend..."         -> segment_1
allStaticTexts[2]  = "Passage Title"               -> segment_2
allStaticTexts[3]  = "Subtitle"                    -> segment_3
allStaticTexts[4]  = passageText.value             -> segment_4
allStaticTexts[5]  = "QUESTIONS"                   -> segment_5
allStaticTexts[6]  = "Answer all questions..."     -> segment_6
allStaticTexts[7]  = "Questions 1-10"              -> segment_7
...
```

---

## 8. Page Integration

### Create/Update Page File

Location: `resources/js/pages/Exam/PracticeXXX/Sections/Reading.vue`

### Import Components

```typescript
import ReadingHeaderIELTSXXX from '@/components/Exam/ReadingIELTSXXX/ReadingHeaderIELTSXXX.vue';
import ReadingFooterIELTSXXX from '@/components/Exam/ReadingIELTSXXX/ReadingFooterIELTSXXX.vue';
import ReadingPart1IELTSXXX from '@/components/Exam/ReadingIELTSXXX/ReadingPart1IELTSXXX.vue';
import ReadingPart2IELTSXXX from '@/components/Exam/ReadingIELTSXXX/ReadingPart2IELTSXXX.vue';
import ReadingPart3IELTSXXX from '@/components/Exam/ReadingIELTSXXX/ReadingPart3IELTSXXX.vue';
import ReadingReviewModalIELTSXXX from '@/components/Exam/ReadingIELTSXXX/ReadingReviewModalIELTSXXX.vue';
```

### Use in Template

```vue
<template>
    <div>
        <ReadingHeaderIELTSXXX
            :part1-notes="part1Ref?.notes || []"
            :part2-notes="part2Ref?.notes || []"
            :part3-notes="part3Ref?.notes || []"
            @show-submit-modal="showSubmitModal = true"
            @font-size-change="handleFontSizeChange"
        />

        <div v-show="currentPart === 1">
            <ReadingPart1IELTSXXX ref="part1Ref" :font-size="fontSize" />
        </div>
        <div v-show="currentPart === 2">
            <ReadingPart2IELTSXXX ref="part2Ref" :font-size="fontSize" />
        </div>
        <div v-show="currentPart === 3">
            <ReadingPart3IELTSXXX ref="part3Ref" :font-size="fontSize" />
        </div>

        <ReadingFooterIELTSXXX
            :current-part="currentPart"
            @change-part="handlePartChange"
            @go-to-question="handleGoToQuestion"
        />

        <ReadingReviewModalIELTSXXX
            v-if="showReviewModal"
            @close="showReviewModal = false"
        />
    </div>
</template>
```

---

## 9. Checklist

### Before Starting
- [ ] Identify source version to copy from (recommend: latest stable, e.g., IELTS002)
- [ ] Have new passage texts ready for all 3 parts
- [ ] Have all question texts ready
- [ ] Know question types for each section

### File Structure
- [ ] Created directory `ReadingIELTSXXX/`
- [ ] All 6 files created and renamed correctly
- [ ] All files have correct suffix (IELTSXXX)

### Global Replacements (All Files)
- [ ] Replaced `IELTS002` with `IELTSXXX`
- [ ] Replaced `ielts002` with `ieltsxxx`

### Part Components (Part1, Part2, Part3)
- [ ] Updated `PANEL_WIDTH_KEY` with unique key
- [ ] Updated `passageText` with new content
- [ ] Updated `answers` object with correct question keys
- [ ] Updated `allStaticTexts` array with all texts
- [ ] Updated template with correct question structure
- [ ] All `data-segment-id` indices match `allStaticTexts` array
- [ ] All `id="question-X"` are sequential and correct

### Page Integration
- [ ] Created/updated page file
- [ ] All imports reference correct component names
- [ ] Component refs are properly connected

### Testing
- [ ] All parts display correctly
- [ ] All questions are answerable
- [ ] Highlighting works on passage text
- [ ] Notes can be added and saved
- [ ] Navigation between parts works
- [ ] Submit modal shows all answers

---

## 10. Quick Reference

### Naming Convention
```
ReadingIELTSXXX
         └── XXX = 3-digit test number (e.g., 002, 016, 017)
```

### LocalStorage Keys Pattern
```
reading-ieltsxxx-part1-panel-width
reading-ieltsxxx-part2-panel-width
reading-ieltsxxx-part3-panel-width
```

### Question Number Ranges (Typical)
| Part | Questions |
|------|-----------|
| Part 1 | 1-13 or 1-14 |
| Part 2 | 14-26 or 15-27 |
| Part 3 | 27-40 or 28-40 |

### Common Question Types
| Type | Answer Options |
|------|----------------|
| Paragraph Matching | A, B, C, D, E, F, G, H |
| TRUE/FALSE/NOT GIVEN | TRUE, FALSE, NOT GIVEN |
| YES/NO/NOT GIVEN | YES, NO, NOT GIVEN |
| Multiple Choice | A, B, C, D |
| Fill-in-the-Blank | Text input |
| Matching Headings | i, ii, iii, iv, v, vi, vii, viii, ix, x |

---

## Example: Creating IELTS016

```bash
# 1. Create directory
mkdir -p resources/js/components/Exam/ReadingIELTS016

# 2. Copy from IELTS002
cp resources/js/components/Exam/ReadingIELTS002/*.vue resources/js/components/Exam/ReadingIELTS016/

# 3. Rename files
cd resources/js/components/Exam/ReadingIELTS016
mv ReadingHeaderIELTS002.vue ReadingHeaderIELTS016.vue
mv ReadingFooterIELTS002.vue ReadingFooterIELTS016.vue
mv ReadingPart1IELTS002.vue ReadingPart1IELTS016.vue
mv ReadingPart2IELTS002.vue ReadingPart2IELTS016.vue
mv ReadingPart3IELTS002.vue ReadingPart3IELTS016.vue
mv ReadingReviewModalIELTS002.vue ReadingReviewModalIELTS016.vue

# 4. Find & Replace in all files:
#    - IELTS002 -> IELTS016
#    - ielts002 -> ielts016

# 5. Update Part1, Part2, Part3 with new content
```

---

**Last Updated:** 2026-02-09
