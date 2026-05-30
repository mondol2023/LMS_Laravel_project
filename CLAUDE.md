# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Development Commands

```bash
# Start development server (runs Laravel, queue, logs, and Vite concurrently)
composer run dev

# Frontend only
npm run dev              # Vite dev server
npm run build            # Production build

# Testing
php artisan test                                    # Run all tests
php artisan test tests/Feature/ExampleTest.php      # Run specific file
php artisan test --filter=testName                  # Filter by test name

# Code quality
vendor/bin/pint --dirty   # Format PHP code (run before commits)
npm run lint              # ESLint for JS/Vue
npm run format            # Prettier for JS/Vue

# Artisan
php artisan make:test --pest <name>      # Create Pest test
php artisan make:model <name> -mf        # Model with migration and factory
```

---

## ieltsmock Application Overview

**ieltsmock** is an IELTS mock exam and practice platform with practice tests, automated scoring, text highlighting, note-taking, and performance analytics.

### Tech Stack
- Laravel 12, Inertia.js v2, Vue 3 (Composition API + TypeScript), Tailwind CSS v4

### Import Path Alias
- `@/*` maps to `./resources/js/*` (configured in tsconfig.json)

---

## Practice Component Architecture

Practice components follow a hierarchical, course-specific naming convention.

### Naming Pattern
- Pattern: `C[Course]Test[Number][Section][Part][Component].vue`
- Example: `C20Test1ListeningPart2.vue` = Course 20, Test 1, Listening, Part 2
- Sections: Listening, Reading, Writing, Speaking
- Parts: Part1-4 (Listening), Part1-3 (Reading)
- Components: Header, Footer, ReviewModal

### Directory Structure
```
resources/js/components/Exam/Practice/
├── C19/Test1/Listening/         # C19 test components
├── C20/Test1...Test4/Listening/ # C20 test components
└── C18/ (future)

resources/js/pages/
├── Exam/Practice001...005/Sections/Listening.vue  # Full section pages
├── Listening/Test5Part1.vue                       # Individual part pages
└── Reading/                                       # Same pattern
```

### Test ID Mapping
| Test ID | Course | Slug |
|---------|--------|------|
| 1-4 | C20 | practice001-004 |
| 5-8 | C19 | practice005-008 |

---

## Key Composables

### useHighlight()
Text highlighting and note-taking for practice components.

**Location**: `resources/js/composables/useHighlight.ts`

**Critical Pattern** - Always scope mouseup events:
```typescript
const contentTextRef = ref<HTMLElement | null>(null);

const handleContentTextSelect = (event: MouseEvent) => {
    // CRITICAL: Prevent selection from other areas triggering highlight
    if (!contentTextRef.value?.contains(event.target as Node)) return;
    // ... highlight logic
};
```

**Usage**:
```typescript
const { highlights, addHighlight, deleteHighlight, applyHighlightsToText } = useHighlight();
```

### useIeltsScoring()
Score calculation and IELTS band conversion.

**Location**: `resources/js/composables/useIeltsScoring.ts`

**Usage**:
```typescript
const { calculateResults, getPerformanceLevel, getPerformanceColor } = useIeltsScoring();
const results = calculateResults(userAnswers, correctAnswers);
```

---

## Part Component Pattern

Each part component should follow this structure:

```typescript
// Script setup
const contentTextRef = ref<HTMLElement | null>(null);
const answers = ref<Record<string, string>>({});
const notes = ref<{ id: number; text: string; note: string; part: string; color: string }[]>([]);
const { highlights, addHighlight, deleteHighlight } = useHighlight();

// Expose for parent container
defineExpose({
    getAnswers: () => answers.value,
    scrollToQuestion: (num: number) => { /* scroll logic */ },
    notes,
    deleteNote: (id: number) => { /* delete logic */ }
});

// Save note with part identifier
const saveNote = () => {
    notes.value.push({
        id: Date.now(),
        text: selectedText,
        note: noteText,
        part: 'Part X',  // REQUIRED for focus note functionality
        color: 'yellow'
    });
};
```

---

## Database Models

### Core Models
- **Exam**: Mock exam configurations
- **Result**: Exam results with scores (exam_type: 'full' | 'partial', modules array)
- **Student**: Student info (roll_number, email, phone)
- **TestAttempt**: Practice test attempts with timing
- **UserAnswer**: Individual question responses
- **Highlight**: Stored text highlights (indexed by phone, exam_id, section, part)
- **Draft**: Auto-saved answer drafts

### Important Relationships
```php
Result::belongsTo(Student::class);    // Always eager-load for roll_number
Result::belongsTo(Exam::class);
TestAttempt::hasMany(UserAnswer::class);
```

---

## Routes for Practice Tests

```php
// Individual part practice (testId 1-8)
Route::get('/listening/test{testId}/{part}', ...);  // testId: [1-8], part: part[1-4]
Route::get('/reading/test{testId}/{part}', ...);    // testId: [1-4], part: part[1-3]

// Full section practice via exam slug
Route::get('exam/{slug}/listening', [ExamController::class, 'listening']);

// Practice results
Route::get('/practice/results/listening/{testNumber}', ...);  // testNumber: [1-8]
Route::post('/practice/results/listening/{testNumber}', ...);
```

---

## Audio Files

Audio files are stored in `public/audio/`:
- C20 Tests 1-4: `listening11.mp3` - `listening14.mp3`
- C19 Tests 1-4: `listening15.mp3` - `listening18.mp3`

Update audio path in Header components when creating new tests.

---

## Creating a New Practice Test

1. **Create component directory**: `Practice/C[XX]/Test[N]/Listening/`
2. **Copy and rename from existing test** (e.g., C20Test1)
3. **Update in each file**:
   - Component names (C20 → C19, Test1 → Test2, etc.)
   - Import paths
   - Audio file path in Header component
   - Correct answers in Part components
4. **Create page file**: `pages/Exam/Practice00X/Sections/Listening.vue`
5. **Update Listening/Index.vue**: Add test card with correct slug
6. **Update routes if needed**: Extend testId/testNumber ranges

---

## IELTS Exam Testing Workflow

When the user says **"test {N} listening"** or **"test {N} reading"**, perform a comprehensive code review of the exam section.

### Usage Examples
- `test 1 listening` → Test IELTS001 Listening section
- `test 1 reading` → Test IELTS001 Reading section
- `test 5 listening` → Test IELTS005 Listening section
- `test 22 reading` → Test IELTS022 Reading section

### Test Number to Folder Mapping
| Test # | Folder | Test # | Folder |
|--------|--------|--------|--------|
| 1 | IELTS001 | 14 | IELTS014 |
| 2 | IELTS002 | 15 | IELTS015 |
| 3 | IELTS003 | 16 | IELTS016 |
| 4 | IELTS004 | 17 | IELTS017 |
| 5 | IELTS005 | 18 | IELTS018 |
| 6 | IELTS006 | 19 | IELTS019 |
| 7 | IELTS007 | 20 | IELTS020 |
| 8 | IELTS008 | 21 | IELTS021 |
| 9 | IELTS009 | 22 | IELTS022 |
| 10 | IELTS010 | 23 | IELTS023 |
| 11 | IELTS011 | 24 | IELTS024 |
| 12 | IELTS012 | 25 | IELTS025 |
| 13 | IELTS013 | 26 | IELTS026 |

**File Path Pattern:** `resources/js/pages/Exam/IELTS{XXX}/Sections/{Section}.vue`

### Test Cases to Verify

| TC | Test Case | What to Check |
|----|-----------|---------------|
| TC-01 | **40 Questions** | `correctAnswers` object has keys 1-40, `compareAnswers` loops 1-40, `total_questions: 40` in submit |
| TC-02 | **Line Highlighting** | Part components have `select-text` class, highlight classes exist (`highlight-yellow`, etc.), `mark` elements with `data-highlight-id` |
| TC-03 | **Note Adding** | `notes` ref in Parts, `saveNote` function, notes include `part` identifier, exposed via `defineExpose` |
| TC-04 | **Note Navigation** | `handleFocusNote` switches to correct part, uses `nextTick()`, searches `mark[data-highlight-id]`, `scrollIntoView`, `@focus-note` event |
| TC-05 | **Flag Feature** | `flaggedQuestions` Set, `handleToggleFlag` function, `@toggle-flag` event, `:flagged-questions` prop to Parts |
| TC-06 | **Multi-line Highlight** | `TreeWalker` or DOM traversal, `Range` API usage, highlight persists across lines |
| TC-07 | **Answer Saving** | `answers` ref in Parts, `getAnswers()` exposed, parent collects all answers, `user_answers` in submit payload |
| TC-08 | **Answer Consistency** | Compare `correctAnswers` in Vue component with `examResults.ts` and `examResults.php` - all three must match |

### TC-08 Answer Consistency Details
**Files to Compare:**
1. Vue Component: `resources/js/pages/Exam/IELTS{XXX}/Sections/{Listening|Reading}.vue` → `correctAnswers` object
2. TypeScript: `resources/js/pages/Results/json/examResults.ts` → find matching exam by slug (ielts{XXX})
3. PHP Config: `config/examResults.php` → find matching exam by slug (ielts{XXX})

**What to Check:**
- All 40 answers match across all 3 files
- Answer format is consistent (case, spacing, alternatives with `/`)
- Report any mismatches with question number and differing values

### Test Report Format

After testing, provide a report:

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

### Recommendations
- Recommendation 1
```

### Instructions for Testing
1. Parse test number and section from user input
2. Map test number to folder (pad with zeros: 1 → 001)
3. Read the Section file (Listening.vue or Reading.vue)
4. Read Part components if needed (in `resources/js/components/Exam/`)
5. Check each test case systematically
6. Generate the test report

### Section Structure Notes
- **Listening**: Parts 1-4, Questions 1-10, 11-20, 21-30, 31-40
- **Reading**: Parts 1-3, Questions 1-13, 14-26, 27-40

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.2.30
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
