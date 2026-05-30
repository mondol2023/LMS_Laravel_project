# Resize Functionality Update Guide

## Overview
This guide documents how to add resize functionality to Reading components. The pattern has been successfully implemented in:
- ✅ ReadingPart1IELTS004.vue (reference implementation)
- ✅ ReadingPart2IELTS004.vue (completed)

## Files Requiring Updates

### IELTS004 (1 remaining)
- [ ] ReadingPart3IELTS004.vue - key: `reading-ielts004-part3-panel-width`

### IELTS005 (3 files)
- [ ] ReadingPart1IELTS005.vue - key: `reading-ielts005-part1-panel-width`
- [ ] ReadingPart2IELTS005.vue - key: `reading-ielts005-part2-panel-width`
- [ ] ReadingPart3IELTS005.vue - key: `reading-ielts005-part3-panel-width`

### IELTS006 (3 files)
- [ ] ReadingPart1IELTS006.vue - key: `reading-ielts006-part1-panel-width`
- [ ] ReadingPart2IELTS006.vue - key: `reading-ielts006-part2-panel-width`
- [ ] ReadingPart3IELTS006.vue - key: `reading-ielts006-part3-panel-width`

**Note:** IELTS007 folder does not exist yet, so those 3 files are not applicable.

## Implementation Steps

### 1. Script Section Updates

#### A. Add Resize State (after `passageTextRef`)
```typescript
// Resize functionality
const PANEL_WIDTH_KEY = 'reading-ieltsXXX-partY-panel-width'; // Replace with correct key
const leftPanelWidth = ref(parseFloat(localStorage.getItem(PANEL_WIDTH_KEY) || '50'));
const isResizing = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);
```

#### B. Add Resize Handlers (after `handleKeyDown`)
```typescript
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

  if (newLeftWidth >= 20 && newLeftWidth <= 80) {
    leftPanelWidth.value = newLeftWidth;
  }
};

const stopResize = () => {
  isResizing.value = false;
};
```

#### C. Add localStorage Watcher (after notes watcher)
```typescript
// Save panel width to localStorage
watch(leftPanelWidth, (newWidth) => {
  localStorage.setItem(PANEL_WIDTH_KEY, newWidth.toString());
});
```

#### D. Update onMounted
Add these two lines before the closing `});`:
```typescript
  document.addEventListener("mousemove", handleResize);
  document.addEventListener("mouseup", stopResize);
```

#### E. Update onBeforeUnmount
Add these two lines before the closing `});`:
```typescript
  document.removeEventListener("mousemove", handleResize);
  document.removeEventListener("mouseup", stopResize);
```

### 2. Template Section Updates

#### A. Update Main Wrapper
**FROM:**
```vue
<template>
  <div class="container mx-auto px-4 py-4 pb-32">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:h-[calc(100vh-8rem)]">
      <!-- Reading Passage -->
      <div
        class="flex flex-col h-[600px] lg:h-full overflow-hidden rounded-r-lg bg-white shadow-lg"
      >
```

**TO:**
```vue
<template>
  <div class="min-h-screen overflow-y-auto pb-20 sm:pb-24 md:pb-32">
    <div class="container mx-auto p-3 sm:p-4 lg:p-6">
      <div
        ref="containerRef"
        class="relative flex flex-col gap-0 lg:flex-row"
        :class="{ 'select-none': isResizing }"
      >
        <!-- Reading Passage -->
        <div
          class="passage-panel mb-4 max-h-screen overflow-y-auto rounded-r-lg bg-white shadow-lg lg:mb-0"
          :style="{ '--panel-width': `${leftPanelWidth}%` }"
        >
        <div
          class="flex flex-col h-[600px] lg:h-full overflow-hidden bg-white"
        >
```

#### B. Close Reading Passage Divs and Add Resize Handle
**BEFORE** the `<!-- Questions Section -->` comment, add closing divs and resize handle:
```vue
          </div> <!-- Close inner content -->
        </div> <!-- Close h-[600px] wrapper -->
        </div> <!-- Close passage-panel -->
        </div> <!-- Close passage outer wrapper if exists -->

        <!-- Resize Handle -->
        <div class="group relative hidden w-4 flex-shrink-0 cursor-col-resize items-center justify-center transition-all hover:bg-blue-50 lg:flex"
          @mousedown="startResize" title="Drag to resize panels">
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="h-24 w-1 rounded-full bg-gray-300 transition-all group-hover:w-1.5 group-hover:bg-blue-500"></div>
          </div>
          <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col gap-1">
            <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
            <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
            <div class="h-1 w-1 rounded-full bg-gray-400 transition-all group-hover:bg-blue-600"></div>
          </div>
        </div>
```

#### C. Update Questions Section Wrapper
**FROM:**
```vue
      <!-- Questions Section -->
      <div
        class="flex h-[600px] lg:h-full flex-col rounded-l-lg bg-white shadow-lg overflow-hidden"
      >
```

**TO:**
```vue
        <!-- Questions Section -->
        <div class="answer-panel flex max-h-screen flex-col overflow-y-auto rounded-l-lg bg-white shadow-lg"
          :style="{ '--panel-width': `${leftPanelWidth}%` }">
        <div
          class="flex h-[600px] lg:h-full flex-col bg-white overflow-hidden"
        >
```

#### D. Close Questions Section Divs
**BEFORE** `<!-- Context Menu for Highlighting -->`, ensure proper closing:
```vue
          </div> <!-- Close questions content -->
        </div> <!-- Close h-[600px] wrapper -->
        </div> <!-- Close answer-panel -->
        </div> <!-- Close containerRef flex wrapper -->
      </div> <!-- Close container -->
    </div> <!-- Close min-h-screen -->
  </div>

  <!-- Context Menu for Highlighting -->
```

### 3. Style Section Updates

#### A. Add select-none Class (at beginning of `<style scoped>`)
```css
<style scoped>
.select-none {
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  cursor: col-resize;
}

/* Rest of existing styles */
```

#### B. Add Responsive Panel Widths (before `</style>`)
```css
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
</style>
```

## Testing Checklist

After updating each file:
- [ ] Component loads without errors
- [ ] Panels are side-by-side on desktop (lg breakpoint)
- [ ] Resize handle appears between panels
- [ ] Dragging the handle resizes panels smoothly
- [ ] Panel width persists after page reload (check localStorage)
- [ ] Mobile view works (stacked layout)
- [ ] Text selection still works in passage
- [ ] Highlight and note functionality still works

## Reference Files
- **Primary Reference:** `resources/js/components/Exam/ReadingIELTS004/ReadingPart1IELTS004.vue`
- **Secondary Reference:** `resources/js/components/Exam/ReadingIELTS004/ReadingPart2IELTS004.vue`

## Notes
- The resize functionality only appears on desktop (lg: breakpoint and above)
- Mobile users see the traditional stacked layout
- Panel widths are constrained between 20% and 80%
- Default width is 50% if no saved preference exists
