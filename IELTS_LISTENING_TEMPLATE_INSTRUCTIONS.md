# IELTS Listening Component Template Instructions

Use this document to replicate the Listening components for IELTS003, IELTS004, IELTS005, etc.

---

## 1. Directory Structure

Create the following structure for each new IELTS version:

```
resources/js/components/Exam/ListeningIELTS[XXX]/
├── ListeningHeaderIELTS[XXX].vue
├── ListeningFooterIELTS[XXX].vue
├── ListeningPart1IELTS[XXX].vue
├── ListeningPart2IELTS[XXX].vue
├── ListeningPart3IELTS[XXX].vue
├── ListeningPart4IELTS[XXX].vue
├── ListeningReviewModalIELTS[XXX].vue
└── ListeningSubmitModalIELTS[XXX].vue

resources/js/pages/Exam/IELTS[XXX]/Sections/
└── Listening.vue
```

---

## 2. Design System - Black & White Theme

### Color Palette
- **Primary**: Black (`bg-black`, `text-black`, `border-black`)
- **Secondary**: White (`bg-white`, `text-white`)
- **Gray accents**: `text-gray-600`, `text-gray-700`, `text-gray-800`, `border-gray-300`
- **Hover states**: `hover:bg-gray-100`, `hover:bg-gray-800`

### Buttons
```html
<!-- Primary Button (Solid Black) -->
<button class="bg-black text-white font-bold py-2 px-4 hover:bg-gray-800 transition-colors">
    Submit
</button>

<!-- Secondary Button (Outline) -->
<button class="border-2 border-black text-black font-bold py-2 px-4 hover:bg-gray-100 transition-colors">
    Go Back
</button>
```

### Question Number Badges
```html
<span class="flex h-6 w-6 items-center justify-center bg-black">
    <span class="text-white text-xs font-bold">1</span>
</span>
```

### Input Fields
```html
<input class="w-28 border border-gray-900 px-3 py-1 text-center" placeholder="Answer" />
```

### Checkboxes & Radio Buttons
- Use `accent-black` for black color styling
```html
<input type="checkbox" class="w-4 h-4 sm:w-5 sm:h-5 accent-black" />
<input type="radio" class="h-4 w-4 sm:h-5 sm:w-5 accent-black" />
```

---

## 3. Remove All Shadows, Borders, Backgrounds from Containers

### What to Remove
- `shadow-sm`, `shadow-md`, `shadow-lg`, `shadow-xl`
- `border`, `border-2`, `border-gray-200`, `border-gray-300` (except on inputs/tables)
- `bg-white`, `bg-gray-50`, `bg-gray-100` (except on inputs/specific elements)

### Container Styling
```html
<!-- Before -->
<div class="border border-gray-200 bg-white p-6 shadow-md">

<!-- After -->
<div class="p-2">
```

### Keep Borders On
- Text inputs
- Table cells
- Modals/dialogs

---

## 4. Minimal Spacing

Use minimal padding throughout:
- Containers: `p-2`
- Question labels: `p-1`
- Gaps: `gap-2`

```html
<!-- Question option label -->
<label class="flex items-start gap-2 cursor-pointer p-1 hover:bg-gray-50 transition-colors">
```

---

## 5. Font Size & Background Color Props

### Part Components Must Accept Props
```typescript
interface Props {
    fontSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
});

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));
```

### Apply Zoom to Content
```html
<div class="min-h-screen flex flex-col w-full" :style="contentZoom">
```

### Parent Page State
```typescript
const fontSize = ref(16);
const bgColor = ref<'white' | 'gray'>('white');

const handleFontSizeChange = (size: number) => {
    fontSize.value = size;
};

const handleBgColorChange = (color: 'white' | 'gray') => {
    bgColor.value = color;
};
```

### Pass to Part Components
```html
<ListeningPart1IELTS[XXX] ref="listeningPart1Ref" :font-size="fontSize" />
```

---

## 6. Note Highlighting System

### Note Color - Use YELLOW
```typescript
...overlappingNotes.map((n) => ({
    start: n.start,
    end: n.end,
    type: 'note' as const,
    color: 'yellow',  // IMPORTANT: Always use yellow
    id: n.id,
    noteText: n.note,
})),
```

### Note Highlight CSS (Non-scoped)
```css
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
```

---

## 7. Previous/Next Navigation Buttons (Floating Arrows)

### Navigation Logic (Add to Parent Page)
```typescript
const partsList: ('part1' | 'part2' | 'part3' | 'part4')[] = ['part1', 'part2', 'part3', 'part4'];
const partLabels: Record<string, string> = { part1: 'Part 1', part2: 'Part 2', part3: 'Part 3', part4: 'Part 4' };
const currentPartIndex = computed(() => partsList.indexOf(activePart.value));
const hasPreviousPart = computed(() => currentPartIndex.value > 0);
const hasNextPart = computed(() => currentPartIndex.value < partsList.length - 1);
const previousPartLabel = computed(() => (hasPreviousPart.value ? partLabels[partsList[currentPartIndex.value - 1]] : ''));
const nextPartLabel = computed(() => (hasNextPart.value ? partLabels[partsList[currentPartIndex.value + 1]] : ''));

const goToPreviousPart = () => {
    if (hasPreviousPart.value) {
        activePart.value = partsList[currentPartIndex.value - 1];
    }
};

const goToNextPart = () => {
    if (hasNextPart.value) {
        activePart.value = partsList[currentPartIndex.value + 1];
    }
};
```

### Template - Add After Part4 Component, Before Footer
```html
<!-- Previous / Next Part Navigation -->
<div class="sticky bottom-24 z-40 ml-2 w-fit flex gap-2">
    <button
        @click="goToPreviousPart"
        :disabled="!hasPreviousPart"
        class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-md transition-all duration-200"
        :class="hasPreviousPart
            ? 'text-gray-700 hover:bg-gray-100 hover:shadow-lg'
            : 'text-gray-300 cursor-not-allowed opacity-50'"
        :title="hasPreviousPart ? previousPartLabel : ''"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>
    <button
        @click="goToNextPart"
        :disabled="!hasNextPart"
        class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 bg-white shadow-md transition-all duration-200"
        :class="hasNextPart
            ? 'text-gray-700 hover:bg-gray-100 hover:shadow-lg'
            : 'text-gray-300 cursor-not-allowed opacity-50'"
        :title="hasNextPart ? nextPartLabel : ''"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>
</div>
```

### Placement in Template
```html
        <div :class="{ hidden: activePart !== 'part4' }">
            <ListeningPart4IELTS[XXX] ref="listeningPart4Ref" :font-size="fontSize" />
        </div>

        <!-- Previous / Next Part Navigation -->  <!-- ADD HERE -->
        <div class="sticky bottom-24 z-40 ml-2 w-fit flex gap-2">
            ...
        </div>

        <ListeningFooterIELTS[XXX]
            ...
        />
```

---

## 8. Responsive Table Design (Part 4)

### Use Card Layout for Mobile, Table for Desktop

```html
<!-- Mobile Card Layout -->
<div class="block lg:hidden space-y-4">
    <div class="border border-gray-300 p-3">
        <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-200">
            <span class="font-bold text-black text-lg">State Name</span>
        </div>
        <div class="space-y-3 text-sm">
            <div>
                <span class="font-semibold text-gray-600">Label:</span>
                <span class="ml-1">Value</span>
            </div>
            <div>
                <span class="font-semibold text-gray-600">Question:</span>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                    <span class="flex h-6 w-6 items-center justify-center bg-black">
                        <span class="text-white text-xs font-bold">35</span>
                    </span>
                    <input v-model="answers.q35" type="text"
                        class="w-32 border border-gray-900 px-3 py-1 text-center"
                        placeholder="Answer" />
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Table Layout -->
<div class="hidden lg:block">
    <table class="w-full border-collapse text-sm">
        <thead>
            <tr>
                <th class="border border-gray-300 p-2 text-left font-bold bg-gray-50">Header</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-gray-300 p-2 align-top">Content</td>
            </tr>
        </tbody>
    </table>
</div>
```

---

## 9. Submit Modal (Short & Cute - Black/White)

```vue
<template>
    <Teleport to="body">
        <div
            v-if="isVisible"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
            @click="handleOverlayClick"
        >
            <div class="w-full max-w-sm bg-white border-2 border-black p-6 text-center">
                <!-- Icon -->
                <div class="flex justify-center mb-4">
                    <div class="h-12 w-12 bg-black flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="text-lg font-bold text-black mb-2">Submit Test?</h2>

                <!-- Stats -->
                <p class="text-sm text-gray-600 mb-4">
                    <span class="font-bold text-black">{{ answeredCount }}</span>/{{ totalQuestions }} answered
                </p>

                <!-- Buttons -->
                <div class="flex gap-2">
                    <button
                        @click="handleClose"
                        class="flex-1 py-2 border-2 border-black text-black font-semibold text-sm hover:bg-gray-100"
                    >
                        Go Back
                    </button>
                    <button
                        @click="handleSubmit"
                        class="flex-1 py-2 bg-black text-white font-semibold text-sm hover:bg-gray-800"
                    >
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
```

---

## 10. Header 3-Dot Menu Functionality

### Features to Implement
1. **Text Size**: Slider 12-24px, emit `font-size-change` event
2. **Brightness**: CSS filter brightness control
3. **Background Color**: Toggle white/gray, emit `bg-color-change` event

### Header Emits
```typescript
const emit = defineEmits<{
    'font-size-change': [size: number];
    'bg-color-change': [color: 'white' | 'gray'];
    'focus-note': [note: any];
}>();
```

---

## 11. Audio File Path

**Keep the original audio path** - do not change unless you have different audio content:
```typescript
audioRef.value.src = '/audio/listening.mp3';
```

Only update if you have a different audio file for this IELTS version.

---

## 12. Correct Answers

Update correct answers in parent Listening.vue for each version:
```typescript
const correctAnswers: Record<number, string> = {
    1: 'answer1',
    2: 'answer2',
    // ... questions 1-40
};
```

---

## 13. Component Naming Convention

Replace `[XXX]` with the IELTS number:
- IELTS002 → ListeningPart1IELTS002.vue
- IELTS003 → ListeningPart1IELTS003.vue
- IELTS004 → ListeningPart1IELTS004.vue

---

## 14. Quick Checklist for New IELTS Version

### Component Setup
- [ ] Create directory `ListeningIELTS[XXX]`
- [ ] Copy all components from original `Listening` folder
- [ ] Rename all files with IELTS[XXX] suffix
- [ ] Copy SubmitModal from IELTS002 and rename

### Page Setup
- [ ] Add `fontSize` and `bgColor` refs
- [ ] Add `handleFontSizeChange` and `handleBgColorChange` handlers
- [ ] Add floating arrows navigation logic (partsList, goToPreviousPart, goToNextPart)
- [ ] Add `@font-size-change` and `@bg-color-change` events to Header
- [ ] Add `:font-size="fontSize"` prop to all Part components
- [ ] Add floating arrows template (after Part4, before Footer)
- [ ] Update main container background to dynamic class

### Keep Original (Unless Different Content)
- [ ] Audio path (only change if different audio file)
- [ ] Footer design/functionality
- [ ] Original question content

### Testing
- [ ] Verify fontSize prop is passed to all parts
- [ ] Test 3-dot menu (text size, brightness, background)
- [ ] Test floating Previous/Next arrows
- [ ] Test note highlighting
- [ ] Test submit modal

---

## 15. Files to Copy as Base

Start by copying files from the **original Listening folder** (keeps your original content):

### Step 1: Copy Components
```bash
# Create directory
mkdir -p resources/js/components/Exam/ListeningIELTS[XXX]

# Copy from original Listening folder
cp resources/js/components/Exam/Listening/*.vue resources/js/components/Exam/ListeningIELTS[XXX]/
```

### Step 2: Rename Files
```bash
cd resources/js/components/Exam/ListeningIELTS[XXX]
mv ListeningFooter.vue ListeningFooterIELTS[XXX].vue
mv ListeningHeader.vue ListeningHeaderIELTS[XXX].vue
mv ListeningPart1.vue ListeningPart1IELTS[XXX].vue
mv ListeningPart2.vue ListeningPart2IELTS[XXX].vue
mv ListeningPart3.vue ListeningPart3IELTS[XXX].vue
mv ListeningPart4.vue ListeningPart4IELTS[XXX].vue
mv ListeningReviewModal.vue ListeningReviewModalIELTS[XXX].vue
```

### Step 3: Create SubmitModal (copy from IELTS002)
```bash
cp resources/js/components/Exam/ListeningIELTS002/ListeningSubmitModalIELTS002.vue \
   resources/js/components/Exam/ListeningIELTS[XXX]/ListeningSubmitModalIELTS[XXX].vue
```

### Step 4: Update Page File
The page file already exists at `resources/js/pages/Exam/IELTS[XXX]/Sections/Listening.vue`
- Add fontSize/bgColor refs and handlers
- Add floating arrows navigation
- Add event bindings to Header
- Pass :font-size prop to Part components

### What to Keep
- **Audio path** - Not Changed
- **Question content** - Already has your original content not Change 

---

## Summary of Key Design Decisions

| Element | Style |
|---------|-------|
| Containers | `p-2`, no shadows/borders/backgrounds |
| Inputs | `border border-gray-900 px-3 py-1` |
| Checkboxes/Radios | `accent-black` |
| Question badges | `bg-black text-white` |
| Buttons (primary) | `bg-black text-white` |
| Buttons (secondary) | `border-2 border-black` |
| Note highlights | Yellow with 📝 badge |
| Modals | `border-2 border-black` |
| Tables (mobile) | Card layout with labels |
| Tables (desktop) | Traditional table with `border border-gray-300` |
