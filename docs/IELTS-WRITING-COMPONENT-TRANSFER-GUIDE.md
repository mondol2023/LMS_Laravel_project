# IELTS Writing Component Transfer Guide

> **Purpose:** Step-by-step instructions for creating new IELTS Writing test versions (e.g., `WritingIELTS016`, `WritingIELTS017`, etc.) from an existing version.

---

## Table of Contents

1. [Directory Structure](#1-directory-structure)
2. [Files Overview](#2-files-overview)
3. [Transfer Process](#3-transfer-process)
4. [File-by-File Changes](#4-file-by-file-changes)
5. [Content Updates for Part Components](#5-content-updates-for-part-components)
6. [Design Style Differences](#6-design-style-differences)
7. [Page Integration](#7-page-integration)
8. [Checklist](#8-checklist)
9. [Quick Reference](#9-quick-reference)

---

## 1. Directory Structure

### Source Location
```
resources/js/components/Exam/Writing/              # Original template (colored design)
resources/js/components/Exam/WritingIELTS002/      # Black/white design (recommended source)
```

### Target Location
```
resources/js/components/Exam/WritingIELTSXXX/      # Replace XXX with your test number (e.g., 016)
```

---

## 2. Files Overview

Each Writing test version contains **4 files**:

| File | Purpose |
|------|---------|
| `WritingHeaderXXX.vue` | Timer, notes drawer, font/brightness controls, word count display, submit button |
| `WritingFooterXXX.vue` | Part 1/Part 2 navigation tabs |
| `WritingPart1IELTSXXX.vue` | Task 1: Graph/Chart/Diagram description (150+ words) |
| `WritingPart2IELTSXXX.vue` | Task 2: Essay question (250+ words) |

### Naming Convention Note
- Header/Footer use short suffix: `WritingHeader002.vue`
- Part components use full suffix: `WritingPart1IELTS002.vue`

---

## 3. Transfer Process

### Step 1: Create Directory
```bash
mkdir -p resources/js/components/Exam/WritingIELTSXXX
```

### Step 2: Copy Files from Source
```bash
cp resources/js/components/Exam/WritingIELTS002/*.vue resources/js/components/Exam/WritingIELTSXXX/
```

### Step 3: Rename All Files
```bash
cd resources/js/components/Exam/WritingIELTSXXX

# Rename each file (note different naming patterns)
mv WritingHeader002.vue WritingHeaderXXX.vue
mv WritingFooter002.vue WritingFooterXXX.vue
mv WritingPart1IELTS002.vue WritingPart1IELTSXXX.vue
mv WritingPart2IELTS002.vue WritingPart2IELTSXXX.vue
```

### Step 4: Global Find & Replace (All Files)

Run these replacements in **ALL 4 files**:

| Find | Replace |
|------|---------|
| `IELTS002` | `IELTSXXX` |
| `ielts002` | `ieltsxxx` |
| `002` (in Header/Footer names only) | `XXX` |

---

## 4. File-by-File Changes

### 4.1 WritingHeaderXXX.vue

**Changes Required:** Only suffix replacements (Step 4 above)

**Key Features (no content changes needed):**
- Timer with color-coded warnings
- Word count display for Part 1 and Part 2
- Notes drawer (shows notes from both parts)
- Font size and brightness controls
- Fullscreen toggle
- Submit button

---

### 4.2 WritingFooterXXX.vue

**Changes Required:** Only suffix replacements (Step 4 above)

**Key Features (no content changes needed):**
- Part 1 / Part 2 navigation buttons
- Active state styling

---

### 4.3 WritingPart1IELTSXXX.vue (MAJOR CHANGES)

This is Task 1 - describing a graph, chart, table, or diagram.

#### A. Update Question Text (Line ~22-27)
```typescript
// FIND and REPLACE the questionText ref:
const questionText = ref(`The diagrams below show the development of small fishing village...

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words`);
```

**Replace with your new Task 1 question.**

#### B. Update Image Path (in template section)
```vue
<!-- FIND in template (around line 388 or 506): -->
<img src="/images/writing/IELTS002.png" alt="..." />

<!-- REPLACE with: -->
<img src="/images/writing/IELTSXXX.png" alt="Your image description" />
```

**Note:** Some tests have multiple images. Check if you need:
- Single image: `IELTSXXX.png`
- Multiple images: `IELTSXXX.1.png`, `IELTSXXX.2.png`

#### C. Image File Setup
Place your image(s) in:
```
public/images/writing/IELTSXXX.png
```

---

### 4.4 WritingPart2IELTSXXX.vue (MAJOR CHANGES)

This is Task 2 - writing an essay on a given topic.

#### A. Update Question Text (Line ~24-31 or ~37-43)
```typescript
// FIND and REPLACE the questionText ref:
const questionText = ref(`Young people in the modern world seem to have more power and influence than previous young generations.

Why is this the case?

What impact does this have on the relationship between old and young people?

Give reasons for your answer and include any relevant examples from your own knowledge or experience.`);
```

**Replace with your new Task 2 essay question.**

#### B. No Image Required
Part 2 (Task 2) is essay-only, no images needed.

---

## 5. Content Updates for Part Components

### 5.1 Task 1 Question Types

Task 1 can include various visual data types:

| Type | Description | Example Text |
|------|-------------|--------------|
| Line Graph | Trends over time | "The graph below shows changes in..." |
| Bar Chart | Comparisons | "The bar chart compares..." |
| Pie Chart | Proportions | "The pie charts show the proportion of..." |
| Table | Data comparison | "The table below gives information about..." |
| Process Diagram | Steps/stages | "The diagram below shows how..." |
| Map | Location changes | "The maps below show..." |
| Multiple Charts | Combined data | "The charts below give information about..." |

### 5.2 Task 2 Question Types

| Type | Structure |
|------|-----------|
| Opinion (Agree/Disagree) | "To what extent do you agree or disagree?" |
| Discussion | "Discuss both views and give your opinion." |
| Problem/Solution | "What are the causes? What solutions can you suggest?" |
| Advantages/Disadvantages | "Do the advantages outweigh the disadvantages?" |
| Two-Part Question | Two related questions to answer |

### 5.3 Standard Word Count Requirements

```typescript
// Task 1 - Always include:
"Write at least 150 words"

// Task 2 - Always include:
"Write at least 250 words"
// or
"You should write at least 250 words."
```

---

## 6. Design Style Differences

### Source Writing/ (Colored Design)
- Rounded corners (`rounded-lg`, `rounded-xl`)
- Colored accents (blue, purple gradients)
- Shadow effects (`shadow-lg`, `shadow-xl`)
- Focus rings with colors (`ring-blue-500`, `ring-purple-500`)

### WritingIELTS002/ (Black/White Design)
- No rounded corners (flat design)
- Black/white/gray color scheme
- Black borders (`border-black`)
- Minimal shadows
- Black focus states

### Key Design Elements to Maintain (IELTS002 Style)

```vue
<!-- Panel styling -->
<div class="bg-white p-4 sm:p-6">

<!-- Borders -->
class="border border-black"
class="border-2 border-black"

<!-- Buttons -->
class="bg-black text-white hover:bg-gray-800"
class="border border-black hover:bg-gray-100"

<!-- Focus states -->
class="focus:border-black focus:outline-none"

<!-- Context menu -->
class="border border-black bg-white p-2"
```

---

## 6.5 Three-Dot Menu Functionality (CRITICAL)

The Header component's three-dot menu provides settings for Fullscreen, Text Size, Brightness, and Background Color. **For these to work, the parent page must handle the emitted events and pass props.**

### Required State Variables in Page

```typescript
// Add these refs in your page component
const fontSize = ref(16);
const bgColor = ref<'white' | 'gray'>('white');
```

### Required Event Handlers in Page

```typescript
// Font size handler - updates fontSize ref when user changes text size
const handleFontSizeChange = (size: number) => {
    fontSize.value = size;
};

// Background color handler - updates bgColor ref when user changes background
const handleBgColorChange = (color: 'white' | 'gray') => {
    bgColor.value = color;
};
```

### Required Header Event Bindings

```vue
<WritingHeaderXXX
    :part1-word-count="part1WordCount"
    :part2-word-count="part2WordCount"
    :is-time-up="isTimeUp"
    :part1-notes="part1Notes"
    :part2-notes="part2Notes"
    :active-part="activePart"
    @show-submit-modal="handleShowSubmitModal"
    @time-up="handleTimeUp"
    @focus-note="handleFocusNote"
    @delete-note="handleDeleteNote"
    @font-size-change="handleFontSizeChange"    <!-- CRITICAL: Required for text size -->
    @bg-color-change="handleBgColorChange"       <!-- CRITICAL: Required for background color -->
/>
```

### Required Part Component Props

```vue
<!-- Pass fontSize to both Part components -->
<WritingPart1IELTSXXX
    ref="writingPart1Ref"
    :font-size="fontSize"                        <!-- CRITICAL: Required for text size to work -->
    @word-count-change="handlePart1WordCount"
    @notes-change="handlePart1NotesChange"
/>

<WritingPart2IELTSXXX
    ref="writingPart2Ref"
    :font-size="fontSize"                        <!-- CRITICAL: Required for text size to work -->
    @word-count-change="handlePart2WordCount"
    @notes-change="handlePart2NotesChange"
/>
```

### Required Dynamic Background Class

```vue
<!-- Main container must have dynamic background class -->
<div class="min-h-screen text-black" :class="bgColor === 'gray' ? 'bg-gray-100' : 'bg-white'">
```

### How Each Setting Works

| Setting | Header Action | Page Handling | Part Component |
|---------|---------------|---------------|----------------|
| **Fullscreen** | Calls `document.requestFullscreen()` | None required | None required |
| **Text Size** | Emits `fontSizeChange` event | Updates `fontSize` ref | Uses `:font-size` prop in `textStyle` computed |
| **Brightness** | Applies CSS filter to `document.documentElement` | None required | None required |
| **Background Color** | Emits `bgColorChange` event | Updates `bgColor` ref | Page uses dynamic class |

### Troubleshooting

If three-dot menu settings are not working:

1. **Text Size not changing?**
   - Check `@font-size-change="handleFontSizeChange"` is on Header
   - Check `:font-size="fontSize"` is on Part components
   - Verify Part component has `textStyle` computed that uses the prop

2. **Background Color not changing?**
   - Check `@bg-color-change="handleBgColorChange"` is on Header
   - Check main div has `:class="bgColor === 'gray' ? 'bg-gray-100' : 'bg-white'"`

3. **Fullscreen not working?**
   - Works automatically - check browser console for fullscreen API errors

4. **Brightness not changing?**
   - Works automatically - applies CSS filter to document

---

## 7. Page Integration

### Create/Update Page File

Location: `resources/js/pages/Exam/IELTSXXX/Sections/Writing.vue`

### Full Page Template

```vue
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// Import your versioned components
import WritingFooterXXX from '@/components/Exam/WritingIELTSXXX/WritingFooterXXX.vue';
import WritingHeaderXXX from '@/components/Exam/WritingIELTSXXX/WritingHeaderXXX.vue';
import WritingPart1IELTSXXX from '@/components/Exam/WritingIELTSXXX/WritingPart1IELTSXXX.vue';
import WritingPart2IELTSXXX from '@/components/Exam/WritingIELTSXXX/WritingPart2IELTSXXX.vue';

interface Props {
    slug: string;
    section: string;
    resultId: number;
}

const props = defineProps<Props>();

// ================================
// State Management
// ================================
const activePart = ref<'part1' | 'part2'>('part1');
const part1WordCount = ref(0);
const part2WordCount = ref(0);
const showSubmitModal = ref(false);
const isTimeUp = ref(false);
const showForceSubmitModal = ref(false);
const isLeavingPage = ref(false);

// Notes state
const part1Notes = ref<Array<{
    id: number;
    text: string;
    selectedText: string;
    note: string;
    start: number;
    end: number;
}>>([]);
const part2Notes = ref<Array<{
    id: number;
    text: string;
    selectedText: string;
    note: string;
    start: number;
    end: number;
}>>([]);

// Component refs
const writingPart1Ref = ref();
const writingPart2Ref = ref();
const fontSize = ref(16);
const bgColor = ref<'white' | 'gray'>('white');
const resultId = ref(props.resultId);

// ================================
// Navigation Handlers
// ================================
const handleNavigate = (part: 'part1' | 'part2') => {
    activePart.value = part;
};

// ================================
// Word Count Handlers
// ================================
const handlePart1WordCount = (count: number) => {
    part1WordCount.value = count;
};

const handlePart2WordCount = (count: number) => {
    part2WordCount.value = count;
};

// ================================
// Notes Handlers
// ================================
const handlePart1NotesChange = (notes: typeof part1Notes.value) => {
    part1Notes.value = notes;
};

const handlePart2NotesChange = (notes: typeof part2Notes.value) => {
    part2Notes.value = notes;
};

const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    // Switch to correct part
    if (note.part === 'Task 1') activePart.value = 'part1';
    else activePart.value = 'part2';

    await nextTick();

    // Call focusNote method on the appropriate child component
    setTimeout(() => {
        if (note.part === 'Task 1') {
            writingPart1Ref.value?.focusNote({
                selectedText: note.selectedText,
                start: note.start,
                end: note.end,
            });
        } else {
            writingPart2Ref.value?.focusNote({
                selectedText: note.selectedText,
                start: note.start,
                end: note.end,
            });
        }
    }, 100);
};

const handleDeleteNote = (data: { id: number; part: string }) => {
    if (data.part === 'Task 1') writingPart1Ref.value?.deleteNote(data.id);
    else writingPart2Ref.value?.deleteNote(data.id);
};

// ================================
// Settings Handlers
// ================================
const handleFontSizeChange = (size: number) => {
    fontSize.value = size;
};

const handleBgColorChange = (color: 'white' | 'gray') => {
    bgColor.value = color;
};

// ================================
// Submit Handlers
// ================================
const handleShowSubmitModal = () => {
    showSubmitModal.value = true;
};

const handleCancelSubmit = () => {
    if (isTimeUp.value) return;
    showSubmitModal.value = false;
};

const navigateToExamList = () => {
    router.visit(`/exam/${props.slug}`);
};

const handleConfirmSubmit = async () => {
    showSubmitModal.value = false;
    isLeavingPage.value = true;
    sessionStorage.removeItem(`writing_visited_${props.slug}`);

    const part1Answers = writingPart1Ref.value?.getAnswers() || { part1: null };
    const part2Answers = writingPart2Ref.value?.getAnswers() || { part2: null };

    // UPDATE THESE QUESTIONS FOR YOUR TEST
    const questionAnswers = [
        {
            q: 'YOUR TASK 1 QUESTION HERE',
            ans: part1Answers.part1 || '',
        },
        {
            q: 'YOUR TASK 2 QUESTION HERE',
            ans: part2Answers.part2 || '',
        },
    ];

    await fetch('/api/results/store-section', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            result_id: resultId.value,
            section: 'writing',
            section_data: questionAnswers,
        }),
    });

    navigateToExamList();
};

const handleTimeUp = () => {
    isTimeUp.value = true;
    showSubmitModal.value = true;
};

const handleForceSubmit = async () => {
    showForceSubmitModal.value = false;
    isLeavingPage.value = true;
    sessionStorage.removeItem(`writing_visited_${props.slug}`);

    const part1Answers = writingPart1Ref.value?.getAnswers() || { part1: null };
    const part2Answers = writingPart2Ref.value?.getAnswers() || { part2: null };

    // UPDATE THESE QUESTIONS FOR YOUR TEST
    const questionAnswers = [
        {
            q: 'YOUR TASK 1 QUESTION HERE',
            ans: part1Answers.part1 || '',
        },
        {
            q: 'YOUR TASK 2 QUESTION HERE',
            ans: part2Answers.part2 || '',
        },
    ];

    await fetch('/api/results/store-section', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            result_id: resultId.value,
            section: 'writing',
            section_data: questionAnswers,
        }),
    });

    navigateToExamList();
};

// ================================
// Browser Control Logic (Exam Security)
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
// Lifecycle Hooks
// ================================
onMounted(() => {
    const hasVisitedWriting = sessionStorage.getItem(`writing_visited_${props.slug}`);
    if (hasVisitedWriting === 'true') {
        showForceSubmitModal.value = true;
    } else {
        sessionStorage.setItem(`writing_visited_${props.slug}`, 'true');
    }

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
    }, 1000);
});

onBeforeUnmount(() => {
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
</script>

<template>
    <Head title="Writing Section - IELTS Mock Test" />

    <div class="min-h-screen text-black" :class="bgColor === 'gray' ? 'bg-gray-100' : 'bg-white'">
        <!-- Header Component -->
        <WritingHeaderXXX
            :part1-word-count="part1WordCount"
            :part2-word-count="part2WordCount"
            :is-time-up="isTimeUp"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :active-part="activePart"
            @show-submit-modal="handleShowSubmitModal"
            @time-up="handleTimeUp"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
            @font-size-change="handleFontSizeChange"
            @bg-color-change="handleBgColorChange"
        />

        <!-- Part Components (both mounted, visibility toggled) -->
        <div :class="{ hidden: activePart !== 'part1' }" class="w-full">
            <WritingPart1IELTSXXX
                ref="writingPart1Ref"
                :font-size="fontSize"
                @word-count-change="handlePart1WordCount"
                @notes-change="handlePart1NotesChange"
            />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }" class="w-full">
            <WritingPart2IELTSXXX
                ref="writingPart2Ref"
                :font-size="fontSize"
                @word-count-change="handlePart2WordCount"
                @notes-change="handlePart2NotesChange"
            />
        </div>

        <!-- Footer Component -->
        <WritingFooterXXX :active-part="activePart" @navigate="handleNavigate" />

        <!-- Force Submit Modal (Exam Security) -->
        <div v-if="showForceSubmitModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/70 p-4">
            <div class="relative z-[10001] w-full max-w-md rounded-lg bg-white p-4 shadow-2xl sm:p-6" @click.stop>
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 sm:h-10 sm:w-10">
                        <svg class="h-4 w-4 text-orange-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 sm:text-lg">Must Submit to Continue</h3>
                </div>
                <p class="mb-6 text-sm text-gray-600 sm:text-base">
                    <span class="font-semibold text-orange-600">
                        You cannot reload, go back, switch tabs, or minimize during the exam.
                    </span>
                    <br /><br />
                    You must submit your exam answers to proceed.
                </p>
                <button @click="handleForceSubmit"
                    class="w-full rounded-lg bg-orange-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-orange-700 sm:px-4 sm:text-sm">
                    Submit Exam Now
                </button>
            </div>
        </div>

        <!-- Submit Confirmation Modal -->
        <div v-if="showSubmitModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
            @click="isTimeUp ? null : handleCancelSubmit">
            <div class="relative z-[10000] w-full max-w-md rounded-lg bg-white p-4 shadow-xl sm:p-6" @click.stop>
                <div class="mb-4 flex items-center gap-3">
                    <div :class="['flex h-8 w-8 items-center justify-center rounded-full sm:h-10 sm:w-10',
                        isTimeUp ? 'bg-orange-100' : 'bg-red-100']">
                        <svg v-if="isTimeUp" class="h-4 w-4 text-orange-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="h-4 w-4 text-red-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 sm:text-lg">
                        {{ isTimeUp ? 'Time is Up!' : 'Submit Test' }}
                    </h3>
                </div>
                <p class="mb-6 text-sm text-gray-600 sm:text-base">
                    <span v-if="isTimeUp" class="font-semibold text-orange-600">
                        Your time has expired. You must submit your test now.
                    </span>
                    <span v-else>
                        Are you sure you want to submit your writing test? This action cannot be undone.
                    </span>
                </p>
                <div class="flex gap-3">
                    <button v-if="!isTimeUp" @click="handleCancelSubmit"
                        class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:text-sm">
                        Continue Test
                    </button>
                    <button @click="handleConfirmSubmit"
                        :class="['rounded-lg px-3 py-2 text-xs font-medium text-white transition-colors sm:px-4 sm:text-sm',
                            isTimeUp ? 'w-full bg-orange-600 hover:bg-orange-700' : 'flex-1 bg-red-600 hover:bg-red-700']">
                        Submit Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
```

### Important: Update Question Texts in Page

In `handleConfirmSubmit` and `handleForceSubmit`, update the `questionAnswers` array with your actual questions:

```typescript
const questionAnswers = [
    {
        q: 'The graph below shows... Summarise the information...',  // Your Task 1 question
        ans: part1Answers.part1 || '',
    },
    {
        q: 'Some people believe that... Discuss both views...',  // Your Task 2 question
        ans: part2Answers.part2 || '',
    },
];
```

---

## 8. Checklist

### Before Starting
- [ ] Identify source version to copy from (recommend: WritingIELTS002)
- [ ] Have Task 1 question text ready
- [ ] Have Task 1 image(s) ready
- [ ] Have Task 2 essay question ready

### File Structure
- [ ] Created directory `WritingIELTSXXX/`
- [ ] All 4 files created and renamed correctly
- [ ] Header file: `WritingHeaderXXX.vue`
- [ ] Footer file: `WritingFooterXXX.vue`
- [ ] Part 1 file: `WritingPart1IELTSXXX.vue`
- [ ] Part 2 file: `WritingPart2IELTSXXX.vue`

### Global Replacements (All Files)
- [ ] Replaced `IELTS002` with `IELTSXXX`
- [ ] Replaced `ielts002` with `ieltsxxx`
- [ ] Replaced `002` with `XXX` in Header/Footer filenames

### Part 1 Component
- [ ] Updated `questionText` with new Task 1 question
- [ ] Updated image path(s) in template
- [ ] Uploaded image(s) to `public/images/writing/`
- [ ] Image alt text updated

### Part 2 Component
- [ ] Updated `questionText` with new Task 2 essay question
- [ ] Included word count requirement (250 words)

### Page Integration
- [ ] Created/updated page file
- [ ] All imports reference correct component names
- [ ] Component refs properly connected

### Page Integration (Three-Dot Menu)
- [ ] Added `fontSize` and `bgColor` refs
- [ ] Added `handleFontSizeChange` handler
- [ ] Added `handleBgColorChange` handler
- [ ] Header has `@font-size-change` event binding
- [ ] Header has `@bg-color-change` event binding
- [ ] Part components have `:font-size="fontSize"` prop
- [ ] Main div has dynamic background class

### Testing
- [ ] Part 1 displays correctly with image
- [ ] Part 2 displays correctly
- [ ] Word count works for both parts
- [ ] Highlighting works on question text
- [ ] Notes can be added and displayed in header
- [ ] Navigation between parts works
- [ ] Timer functions correctly
- [ ] **Three-dot menu: Fullscreen works**
- [ ] **Three-dot menu: Text Size slider changes font size**
- [ ] **Three-dot menu: Brightness slider changes brightness**
- [ ] **Three-dot menu: Background Color buttons change background**

---

## 9. Quick Reference

### Naming Convention
```
Directory:    WritingIELTSXXX/
Header:       WritingHeaderXXX.vue      (short suffix)
Footer:       WritingFooterXXX.vue      (short suffix)
Part 1:       WritingPart1IELTSXXX.vue  (full suffix)
Part 2:       WritingPart2IELTSXXX.vue  (full suffix)
```

### Image Paths
```
Single image:     /images/writing/IELTSXXX.png
Multiple images:  /images/writing/IELTSXXX.1.png
                  /images/writing/IELTSXXX.2.png
```

### Word Count Requirements
| Task | Minimum Words |
|------|---------------|
| Task 1 | 150 words |
| Task 2 | 250 words |

### Time Allocation (60 minutes total)
| Task | Recommended Time |
|------|------------------|
| Task 1 | 20 minutes |
| Task 2 | 40 minutes |

### Component Props Summary

**Header Props:**
```typescript
part1WordCount: number
part2WordCount: number
part1Notes: Array<NoteType>
part2Notes: Array<NoteType>
activePart: 'part1' | 'part2'
testTitle?: string
timeLimit?: number (default: 60)
```

**Header Emits:**
```typescript
timeUp: []
showSubmitModal: []
focusNote: [note: { part: string; selectedText: string; start: number; end: number }]
deleteNote: [data: { id: number; part: string }]
fontSizeChange: [size: number]      // CRITICAL: For text size to work
bgColorChange: [color: 'white' | 'gray']  // CRITICAL: For background color to work
```

**Part Component Props:**
```typescript
fontSize?: number  // CRITICAL: For text size changes to apply
```

**Footer Props:**
```typescript
activePart: 'part1' | 'part2'
```

**Part Component Emits:**
```typescript
wordCountChange: [count: number]
notesChange: [notes: Array<NoteType>]
```

**Part Component Exposes:**
```typescript
getAnswers(): { part1/part2: string | null }
deleteNote(id: number): void
focusNote(noteData: NoteType): void
notes: Ref<Array<NoteType>>
```

---

## Example: Creating IELTS016

```bash
# 1. Create directory
mkdir -p resources/js/components/Exam/WritingIELTS016

# 2. Copy from IELTS002
cp resources/js/components/Exam/WritingIELTS002/*.vue resources/js/components/Exam/WritingIELTS016/

# 3. Rename files
cd resources/js/components/Exam/WritingIELTS016
mv WritingHeader002.vue WritingHeader016.vue
mv WritingFooter002.vue WritingFooter016.vue
mv WritingPart1IELTS002.vue WritingPart1IELTS016.vue
mv WritingPart2IELTS002.vue WritingPart2IELTS016.vue

# 4. Find & Replace in all files:
#    - IELTS002 -> IELTS016
#    - ielts002 -> ielts016
#    - 002 -> 016 (in component names)

# 5. Update Part1 with new Task 1 question and image
# 6. Update Part2 with new Task 2 essay question
# 7. Upload image to public/images/writing/IELTS016.png
```

---

## Task 1 Question Template

```typescript
const questionText = ref(`[Description of what the visual shows]

Summarise the information by selecting and reporting the main features, and make comparisons where relevant.

Write at least 150 words`);
```

## Task 2 Question Template

```typescript
const questionText = ref(`[Topic statement or observation]

[Question type - e.g., "Discuss both views and give your own opinion."]

Give reasons for your answer and include any relevant examples from your own knowledge or experience.

Write at least 250 words.`);
```

---

**Last Updated:** 2026-02-09
