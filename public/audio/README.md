# Audio Files for IELTS Listening Test

Place your IELTS listening test audio file here.

## Required File
- **File name**: `listening.mp3`
- **Format**: MP3 audio file
- **Duration**: Approximately 30-35 minutes (standard IELTS listening test duration)

## File Structure
```
public/audio/
└── listening.mp3  ← Your IELTS listening test audio file
```

## Notes
- The audio will auto-play when the test starts
- Students cannot pause or control the audio (as per IELTS test rules)
- The audio path is configured in: `resources/js/components/Exam/Listening/LinstenHeader.vue`
- If you need to change the filename, update line 79 in the LinstenHeader component

## Audio Requirements
- Clear audio quality
- Standard IELTS test format with 4 parts
- Total duration should match the 32-minute test timer