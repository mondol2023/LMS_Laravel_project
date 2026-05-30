// Reading Questions Data for Results Page Description Tab
// This file contains the question text/context for each Reading test

export interface QuestionData {
    number: number;
    type: 'fill-blank' | 'true-false-notgiven' | 'multiple-choice' | 'matching' | 'heading';
    category?: string; // e.g., "Appearance", "Movement", "Feeding"
    context: string; // The question text with ___ for blanks
    instruction?: string; // e.g., "Choose ONE WORD AND/OR A NUMBER"
}

export interface PartData {
    partNumber: number;
    title: string;
    instructions: string;
    questions: QuestionData[];
}

export interface ReadingTestData {
    testId: string;
    testName: string;
    parts: PartData[];
}

// Practice Test 2 (C20 Test 2) - Manatees
export const practice002Reading: ReadingTestData = {
    testId: 'practice002',
    testName: 'Cambridge 20 Test 2',
    parts: [
        {
            partNumber: 1,
            title: 'Manatees',
            instructions: 'Questions 1-13 are based on Reading Passage 1.',
            questions: [
                // Questions 1-6: Fill in the blank
                {
                    number: 1,
                    type: 'fill-blank',
                    category: 'Appearance',
                    context: 'look similar to dugongs, but with a differently shaped ___',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 2,
                    type: 'fill-blank',
                    category: 'Movement',
                    context: 'need to use their ___ to help to turn their bodies around in order to look sideways',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 3,
                    type: 'fill-blank',
                    category: 'Movement',
                    context: 'sense vibrations in the water by means of ___ on their skin',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 4,
                    type: 'fill-blank',
                    category: 'Feeding',
                    context: 'eat mainly aquatic vegetation, such as ___',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 5,
                    type: 'fill-blank',
                    category: 'Feeding',
                    context: 'grasp and pull up plants with their ___',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 6,
                    type: 'fill-blank',
                    category: 'Breathing',
                    context: 'may regulate the ___ of their bodies by using muscles of diaphragm to store air internally',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                // Questions 7-13: TRUE/FALSE/NOT GIVEN
                {
                    number: 7,
                    type: 'true-false-notgiven',
                    context: 'West Indian manatees can be found in a variety of different aquatic habitats.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 8,
                    type: 'true-false-notgiven',
                    context: 'The Florida manatee lives in warmer waters than the Antillean manatee.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 9,
                    type: 'true-false-notgiven',
                    context: "The African manatee's range is limited to coastal waters between the West African countries of Mauritania and Angola.",
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 10,
                    type: 'true-false-notgiven',
                    context: 'The extent of the loss of Amazonian manatees in the mid-twentieth century was only revealed many years later.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 11,
                    type: 'true-false-notgiven',
                    context: 'It is predicted that West Indian manatee populations will fall in the coming decades.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 12,
                    type: 'true-false-notgiven',
                    context: 'The risk to manatees from entanglement and plastic consumption increased significantly in the period 2009-2020.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 13,
                    type: 'true-false-notgiven',
                    context: 'There is some legislation in place which aims to reduce the likelihood of boat strikes on manatees in Florida.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
            ],
        },
    ],
};

// Practice Test 1 (C20 Test 1) - Kākāpō
export const practice001Reading: ReadingTestData = {
    testId: 'practice001',
    testName: 'Cambridge 20 Test 1',
    parts: [
        {
            partNumber: 1,
            title: 'The Kākāpō',
            instructions: 'Questions 1-13 are based on Reading Passage 1.',
            questions: [
                // Questions 1-6: TRUE/FALSE/NOT GIVEN
                {
                    number: 1,
                    type: 'true-false-notgiven',
                    context: "There are other parrots that share the kākāpō's inability to fly.",
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 2,
                    type: 'true-false-notgiven',
                    context: 'Adult kākāpō produce chicks every year.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 3,
                    type: 'true-false-notgiven',
                    context: 'Adult male kākāpō bring food back to nesting females.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 4,
                    type: 'true-false-notgiven',
                    context: 'The Polynesian rat was a greater threat to the kākāpō than Polynesian settlers.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 5,
                    type: 'true-false-notgiven',
                    context: 'Kākāpō were transferred from Rakiura Island to other locations because they were at risk from feral cats.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                {
                    number: 6,
                    type: 'true-false-notgiven',
                    context: 'One Recovery Plan initiative that helped increase the kākāpō population size was caring for struggling young birds.',
                    instruction: 'Write TRUE, FALSE or NOT GIVEN.',
                },
                // Questions 7-13: Fill in the blank
                {
                    number: 7,
                    type: 'fill-blank',
                    category: 'A type of parrot',
                    context: 'diet consists of fern fronds, various parts of a tree and ___',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 8,
                    type: 'fill-blank',
                    category: 'A type of parrot',
                    context: 'nests are created in ___ where eggs are laid',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 9,
                    type: 'fill-blank',
                    category: 'A type of parrot',
                    context: 'the ___ of the kākāpō were used to make clothes',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 10,
                    type: 'fill-blank',
                    category: 'A type of parrot',
                    context: "___ were an animal which they introduced that ate the kākāpō's food sources",
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 11,
                    type: 'fill-blank',
                    category: 'Protecting kākāpō',
                    context: 'a definite sighting of female kākāpō on Rakiura Island was reported in the year ___',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 12,
                    type: 'fill-blank',
                    category: 'Protecting kākāpō',
                    context: 'the Recovery Plan included an increase in ___',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
                {
                    number: 13,
                    type: 'fill-blank',
                    category: 'Protecting kākāpō',
                    context: 'a current goal of the Recovery Plan is to maintain the involvement of ___ in kākāpō protection',
                    instruction: 'Choose ONE WORD AND/OR A NUMBER from the passage for each answer.',
                },
            ],
        },
    ],
};

// Map of all reading questions by test ID
export const readingQuestionsMap: Record<string, ReadingTestData> = {
    practice001: practice001Reading,
    practice002: practice002Reading,
    // Add more tests here as needed
};

// Helper function to get questions for a specific test
export const getReadingQuestions = (testId: string): ReadingTestData | null => {
    return readingQuestionsMap[testId] || null;
};

// Helper to get question by number
export const getQuestionByNumber = (testId: string, questionNumber: number): QuestionData | null => {
    const testData = readingQuestionsMap[testId];
    if (!testData) return null;

    for (const part of testData.parts) {
        const question = part.questions.find((q) => q.number === questionNumber);
        if (question) return question;
    }
    return null;
};
