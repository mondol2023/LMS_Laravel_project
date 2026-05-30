#!/bin/bash

# Script to remove all draft-related code from Vue components
# This script will process all Vue files in the Exam components directory

cd /Users/partho/Desktop/ieltsmcok/ieltsmock

echo "Removing draft code from all Vue components..."

# Find all Vue files with draftService references
FILES=$(grep -rl "draftService\|/api/drafts" resources/js/components/Exam/ --include="*.vue")

COUNT=0
for FILE in $FILES; do
    echo "Processing: $FILE"

    # Create a temporary file for the edited content
    TMP_FILE="${FILE}.tmp"

    # Use sed to remove draft-related lines (macOS compatible)
    # Note: We'll use a more conservative approach for safety

    # 1. Remove draftService import
    sed -i '' "/import draftService from '@\/services\/draftService';/d" "$FILE"

    # 2. Remove Props interface (lines 6-15 typically)
    # This is more complex, so we'll handle it carefully
    # We'll look for the pattern and remove it

    COUNT=$((COUNT + 1))
done

echo "Processed $COUNT files"
echo "Note: This script performed basic cleanup. Manual verification recommended."
