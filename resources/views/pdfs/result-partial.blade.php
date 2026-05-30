<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IELTS Partial Test Results - {{ $result->username }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #1f2937;
            padding: 8px 12px;
        }

        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .column {
            display: table-cell;
            width: 50%;
            padding: 0 4px;
            vertical-align: top;
        }

        .answer-item {
            background: transparent;
            border: 1px solid #e5e7eb;
            padding: 3px 5px;
            margin-bottom: 2px;
            border-radius: 2px;
            font-size: 8px;
        }

        .answer-item.correct {
            background: transparent;
            border-left: 2px solid #10b981;
        }

        .answer-item.wrong {
            background: transparent;
            border-left: 2px solid #ef4444;
        }

        .answer-item .q-num {
            font-weight: bold;
            color: #374151;
            margin-right: 4px;
        }

        .answer-item .user-ans {
            color: #1f2937;
        }

        .answer-item .correct-ans {
            color: #059669;
            font-weight: 600;
            font-size: 7px;
            margin-top: 1px;
            display: block;
        }

        .answer-item .result-icon {
            float: right;
            font-weight: bold;
            font-size: 9px;
        }

        .answer-item.correct .result-icon {
            color: #10b981;
        }

        .answer-item.wrong .result-icon {
            color: #ef4444;
        }

        .writing-container {
            background: transparent;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .writing-task-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .question-box {
            background: transparent;
            padding: 8px;
            margin: 6px 0;
            border-radius: 3px;
            border-left: 3px solid #1e40af;
        }

        .question-box .label {
            font-weight: bold;
            color: #1e40af;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .question-box .text {
            color: #1f2937;
            font-size: 9px;
            line-height: 1.4;
        }

        .answer-box {
            background: transparent;
            padding: 8px;
            margin: 6px 0;
            border-radius: 3px;
            border: 1px solid #e5e7eb;
        }

        .answer-box .label {
            font-weight: bold;
            color: #374151;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .answer-box .text {
            color: #1f2937;
            font-size: 9px;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .feedback-box {
            background: transparent;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #2563eb;
            border: 1px solid #e5e7eb;
        }

        .feedback-box .label {
            font-weight: bold;
            color: #1e40af;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .feedback-box .text {
            color: #1e3a8a;
            font-size: 9px;
            line-height: 1.5;
        }

        .page-break {
            page-break-after: always;
        }

        .page-container {
            min-height: 95vh;
            position: relative;
            padding-bottom: 40px;
        }

        .page-header {
            background: transparent;
            border: 2px solid #1e40af;
            padding: 12px 15px;
            margin: 0 0 12px 0;
            border-radius: 6px;
        }

        .header-logo {
            text-align: center;
        }

        .header-logo img {
            width: 100px;
            display: inline-block;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 12px;
            text-align: center;
            color: #64748b;
            font-size: 8px;
            border-top: 1px solid #e5e7eb;
            background: white;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.06;
            z-index: -1;
            pointer-events: none;
            font-size: 80px;
            font-weight: 900;
            color: #1e40af;
            letter-spacing: 8px;
            white-space: nowrap;
            font-family: 'Arial Black', 'Arial Bold', sans-serif;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 8px;
        }
    </style>
</head>
<body>
<!-- Watermark (appears on all pages) -->
<div class="watermark"></div>

<!-- Page Footer (appears on all pages) -->
<div class="page-footer">
    <strong style="color: #1e40af;">Authorized by English Therapy</strong>
</div>

<!-- SUMMARY PAGE (Partial Test - No Overall Score) -->
<div class="page-container">
    <!-- Summary Header -->
    <div class="page-header" style="border: 3px solid #1e40af;">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; vertical-align: middle; width: 50%;">
                <div style="font-size: 16px; font-weight: bold; color: #1e40af; margin-bottom: 8px;">IELTS PARTIAL MOCK TEST RESULTS</div>
                <div style="font-size: 10px; color: #64748b; line-height: 1.6;">
                    <div><strong style="color: #1f2937;">Name:</strong> {{ $result->username }}</div>
                    @if($result->phone)
                        <div style="margin-top: 3px;"><strong style="color: #1f2937;">Phone:</strong> {{ $result->phone }}</div>
                    @endif
                    @if($result->email)
                        <div style="margin-top: 3px;"><strong style="color: #1f2937;">Email:</strong> {{ $result->email }}</div>
                    @endif
                    @if($result->passport)
                        <div style="margin-top: 3px;"><strong style="color: #1f2937;">ID:</strong> {{ $result->passport }}</div>
                    @endif
                    <div style="margin-top: 3px;"><strong style="color: #1f2937;">Date:</strong> {{ $result->created_at->format('d M Y') }}</div>
                </div>
            </div>
            <div style="display: table-cell; vertical-align: middle; text-align: center; width: 50%;">
                <div class="header-logo">
                    <img src="{{ public_path('logo.png') }}" alt="English Therapy">
                </div>
                <div style="margin-top: 10px; background: #fef3c7; border: 2px solid #f59e0b; padding: 10px 15px; border-radius: 8px; display: inline-block;">
                    <div style="font-size: 9px; font-weight: bold; color: #92400e; letter-spacing: 0.5px;">PARTIAL TEST</div>
                    <div style="font-size: 8px; color: #92400e; margin-top: 2px;">Module-Based Evaluation</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modules Tested Badge -->
    <div style="margin-bottom: 15px; text-align: center;">
        <div style="display: inline-block; background: #dbeafe; border: 1px solid #3b82f6; padding: 8px 15px; border-radius: 6px;">
            <span style="display: inline-block; font-size: 9px; font-weight: bold; color: #1e40af; margin-right: 8px; vertical-align: middle;">MODULES TESTED:</span>
            @foreach($result->modules as $module)
                <span style="display: inline-block; background: #1e40af; color: white; padding: 3px 8px; border-radius: 4px; font-size: 8px; font-weight: 600; vertical-align: middle;">
                    {{ strtoupper($module) }}
                </span>
            @endforeach
        </div>
    </div>

    <!-- Section Scores Grid (Only for included modules) -->
    <div style="margin-top: 15px;">
        <div style="display: table; width: 100%; border-collapse: separate; border-spacing: 8px;">
            <div style="display: table-row;">
                @php
                    $moduleConfig = [
                        'listening' => [
                            'bg' => '#fef3c7',
                            'border' => '#f59e0b',
                            'text' => '#92400e',
                            'score' => '#b45309',
                            'label' => 'LISTENING'
                        ],
                        'reading' => [
                            'bg' => '#dbeafe',
                            'border' => '#3b82f6',
                            'text' => '#1e40af',
                            'score' => '#1e40af',
                            'label' => 'READING'
                        ],
                        'writing' => [
                            'bg' => '#fae8ff',
                            'border' => '#a855f7',
                            'text' => '#7e22ce',
                            'score' => '#7e22ce',
                            'label' => 'WRITING'
                        ],
                        'speaking' => [
                            'bg' => '#dcfce7',
                            'border' => '#22c55e',
                            'text' => '#166534',
                            'score' => '#166534',
                            'label' => 'SPEAKING'
                        ]
                    ];

                    $modulesToShow = $result->modules ?? [];
                    $cellWidth = count($modulesToShow) > 0 ? (100 / count($modulesToShow)) . '%' : '100%';
                @endphp

                @foreach($modulesToShow as $module)
                    @php $config = $moduleConfig[$module] ?? $moduleConfig['listening']; @endphp
                    <!-- {{ ucfirst($module) }} -->
                    <div style="display: table-cell; width: {{ $cellWidth }}; vertical-align: top;">
                        <div style="background: {{ $config['bg'] }}; border: 2px solid {{ $config['border'] }}; padding: 15px; border-radius: 8px; text-align: center; min-height: 85px; height: 85px;">
                            <div style="font-size: 10px; font-weight: bold; color: {{ $config['text'] }}; margin-bottom: 8px;">{{ $config['label'] }}</div>
                            @if($module === 'listening' && isset($result->listening['band_score']) && $result->listening['band_score'] !== null)
                                <div style="font-size: 28px; font-weight: bold; color: {{ $config['score'] }};">{{ $result->listening['band_score'] }}</div>
                                <div style="font-size: 8px; color: {{ $config['text'] }}; margin-top: 4px;">{{ $listeningStats['correct'] ?? 0 }}/{{ $listeningStats['total'] ?? 40 }} correct</div>
                            @elseif($module === 'reading' && isset($result->reading['band_score']) && $result->reading['band_score'] !== null)
                                <div style="font-size: 28px; font-weight: bold; color: {{ $config['score'] }};">{{ $result->reading['band_score'] }}</div>
                                <div style="font-size: 8px; color: {{ $config['text'] }}; margin-top: 4px;">{{ $readingStats['correct'] ?? 0 }}/{{ $readingStats['total'] ?? 40 }} correct</div>
                            @elseif($module === 'writing' && isset($result->writing['band_score']) && $result->writing['band_score'] !== null)
                                <div style="font-size: 28px; font-weight: bold; color: {{ $config['score'] }};">{{ $result->writing['band_score'] }}</div>
                                @if(isset($result->writing['task1_band']) && isset($result->writing['task2_band']))
                                    <div style="font-size: 8px; color: {{ $config['text'] }}; margin-top: 4px;">T1: {{ $result->writing['task1_band'] }} | T2: {{ $result->writing['task2_band'] }}</div>
                                @endif
                            @elseif($module === 'speaking' && isset($result->speaking['band_score']) && $result->speaking['band_score'] !== null)
                                <div style="font-size: 28px; font-weight: bold; color: {{ $config['score'] }};">{{ $result->speaking['band_score'] }}</div>
                                <div style="font-size: 8px; color: {{ $config['text'] }}; margin-top: 4px; visibility: hidden;">Placeholder</div>
                            @else
                                <div style="font-size: 14px; color: {{ $config['text'] }}; font-style: italic;">Pending</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Exam Info -->
    <div style="margin-top: 20px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <div style="font-size: 9px; color: #64748b; text-align: center;">
            <strong style="color: #1f2937;">Exam:</strong> {{ $result->exam->name ?? 'N/A' }}
            <span style="margin: 0 10px; color: #cbd5e1;">|</span>
            <strong style="color: #1f2937;">Exam ID:</strong> {{ $result->exam->exam_id ?? 'N/A' }}
        </div>
    </div>
</div>

<!-- LISTENING PAGE (if included in modules) -->
@if(in_array('listening', $result->modules ?? []) && count($listeningAnswers) > 0)
    <!-- Page Break before Listening -->
    <div class="page-break"></div>
    <div class="page-container">
        <!-- Listening Header -->
        <div class="page-header">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; vertical-align: middle; width: 35%;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af; margin-bottom: 6px;">LISTENING SECTION</div>
                    <div style="font-size: 9px; color: #64748b; line-height: 1.5;">
                        <div><strong style="color: #1f2937;">Name:</strong> {{ $result->username }}</div>
                        @if($result->phone)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Phone:</strong> {{ $result->phone }}</div>
                        @endif
                        @if($result->email)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Email:</strong> {{ $result->email }}</div>
                        @endif
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: center; width: 30%;">
                    <div class="header-logo">
                        <img src="{{ public_path('logo.png') }}" alt="English Therapy">
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: right; width: 35%;">
                    @if(isset($result->listening['band_score']))
                        <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; padding: 10px 18px; border-radius: 8px; display: inline-block;">
                            <div style="font-size: 8px; font-weight: 600; letter-spacing: 0.5px;">BAND SCORE</div>
                            <div style="font-size: 24px; font-weight: bold; line-height: 1;">{{ $result->listening['band_score'] }}</div>
                        </div>
                    @endif
                    <div style="font-size: 10px; color: #64748b; margin-top: 6px;">
                        <span style="color: #10b981; font-weight: 700; font-size: 11px;">✓ {{ $listeningStats['correct'] }}</span>
                        <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                        <span style="color: #ef4444; font-weight: 700; font-size: 11px;">✗ {{ $listeningStats['wrong'] }}</span>
                        <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                        <span style="color: #1f2937; font-weight: 600;">Total: {{ $listeningStats['total'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listening Answers -->
        <div class="two-column">
            <div class="column">
                @foreach($listeningAnswers as $index => $answer)
                    @if($index < ceil(count($listeningAnswers) / 2))
                        <div class="answer-item {{ $answer['isCorrect'] ? 'correct' : 'wrong' }}">
                            <span class="q-num">Q{{ $answer['question'] }}:</span>
                            <span class="user-ans">{{ $answer['userAnswer'] }}</span>
                            <span class="result-icon">{{ $answer['isCorrect'] ? '✓' : '✗' }}</span>
                            @if(!$answer['isCorrect'])
                                <span class="correct-ans">Correct: {{ $answer['correctAnswer'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="column">
                @foreach($listeningAnswers as $index => $answer)
                    @if($index >= ceil(count($listeningAnswers) / 2))
                        <div class="answer-item {{ $answer['isCorrect'] ? 'correct' : 'wrong' }}">
                            <span class="q-num">Q{{ $answer['question'] }}:</span>
                            <span class="user-ans">{{ $answer['userAnswer'] }}</span>
                            <span class="result-icon">{{ $answer['isCorrect'] ? '✓' : '✗' }}</span>
                            @if(!$answer['isCorrect'])
                                <span class="correct-ans">Correct: {{ $answer['correctAnswer'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif

<!-- READING PAGE (if included in modules) -->
@if(in_array('reading', $result->modules ?? []) && count($readingAnswers) > 0)
    <!-- Page Break before Reading -->
    <div class="page-break"></div>
    <div class="page-container">
        <!-- Reading Header -->
        <div class="page-header">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; vertical-align: middle; width: 35%;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af; margin-bottom: 6px;">READING SECTION</div>
                    <div style="font-size: 9px; color: #64748b; line-height: 1.5;">
                        <div><strong style="color: #1f2937;">Name:</strong> {{ $result->username }}</div>
                        @if($result->phone)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Phone:</strong> {{ $result->phone }}</div>
                        @endif
                        @if($result->email)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Email:</strong> {{ $result->email }}</div>
                        @endif
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: center; width: 30%;">
                    <div class="header-logo">
                        <img src="{{ public_path('logo.png') }}" alt="English Therapy">
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: right; width: 35%;">
                    @if(isset($result->reading['band_score']))
                        <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; padding: 10px 18px; border-radius: 8px; display: inline-block;">
                            <div style="font-size: 8px; font-weight: 600; letter-spacing: 0.5px;">BAND SCORE</div>
                            <div style="font-size: 24px; font-weight: bold; line-height: 1;">{{ $result->reading['band_score'] }}</div>
                        </div>
                    @endif
                    <div style="font-size: 10px; color: #64748b; margin-top: 6px;">
                        <span style="color: #10b981; font-weight: 700; font-size: 11px;">✓ {{ $readingStats['correct'] }}</span>
                        <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                        <span style="color: #ef4444; font-weight: 700; font-size: 11px;">✗ {{ $readingStats['wrong'] }}</span>
                        <span style="margin: 0 6px; color: #cbd5e1;">|</span>
                        <span style="color: #1f2937; font-weight: 600;">Total: {{ $readingStats['total'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reading Answers -->
        <div class="two-column">
            <div class="column">
                @foreach($readingAnswers as $index => $answer)
                    @if($index < ceil(count($readingAnswers) / 2))
                        <div class="answer-item {{ $answer['isCorrect'] ? 'correct' : 'wrong' }}">
                            <span class="q-num">Q{{ $answer['question'] }}:</span>
                            <span class="user-ans">{{ $answer['userAnswer'] }}</span>
                            <span class="result-icon">{{ $answer['isCorrect'] ? '✓' : '✗' }}</span>
                            @if(!$answer['isCorrect'])
                                <span class="correct-ans">Correct: {{ $answer['correctAnswer'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="column">
                @foreach($readingAnswers as $index => $answer)
                    @if($index >= ceil(count($readingAnswers) / 2))
                        <div class="answer-item {{ $answer['isCorrect'] ? 'correct' : 'wrong' }}">
                            <span class="q-num">Q{{ $answer['question'] }}:</span>
                            <span class="user-ans">{{ $answer['userAnswer'] }}</span>
                            <span class="result-icon">{{ $answer['isCorrect'] ? '✓' : '✗' }}</span>
                            @if(!$answer['isCorrect'])
                                <span class="correct-ans">Correct: {{ $answer['correctAnswer'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif

<!-- WRITING PAGE (if included in modules) -->
@if(in_array('writing', $result->modules ?? []) && $result->writing)
    <!-- Page Break before Writing -->
    <div class="page-break"></div>
    <div class="page-container">
        <!-- Writing Header -->
        <div class="page-header">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; vertical-align: middle; width: 35%;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af; margin-bottom: 6px;">WRITING SECTION</div>
                    <div style="font-size: 9px; color: #64748b; line-height: 1.5;">
                        <div><strong style="color: #1f2937;">Name:</strong> {{ $result->username }}</div>
                        @if($result->phone)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Phone:</strong> {{ $result->phone }}</div>
                        @endif
                        @if($result->email)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Email:</strong> {{ $result->email }}</div>
                        @endif
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: center; width: 30%;">
                    <div class="header-logo">
                        <img src="{{ public_path('logo.png') }}" alt="English Therapy">
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: right; width: 35%;">
                    @if(isset($result->writing['band_score']))
                        <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; padding: 10px 18px; border-radius: 8px; display: inline-block;">
                            <div style="font-size: 8px; font-weight: 600; letter-spacing: 0.5px;">BAND SCORE</div>
                            <div style="font-size: 24px; font-weight: bold; line-height: 1;">{{ $result->writing['band_score'] }}</div>
                        </div>
                    @else
                        <div style="color: #64748b; font-size: 9px; font-style: italic;">Assessment pending</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Writing Content -->
        <!-- Task Scores Display -->
        @if(isset($result->writing['task1_band']) || isset($result->writing['task2_band']))
            <div style="background: transparent; padding: 12px; margin-bottom: 12px; border: 2px solid #e5e7eb; border-radius: 6px;">
                <div style="display: table; width: 100%;">
                    @if(isset($result->writing['task1_band']))
                        <div style="display: table-cell; width: 50%; padding-right: 6px;">
                            <div style="background: #dbeafe; border: 2px solid #3b82f6; padding: 10px; border-radius: 6px; text-align: center;">
                                <div style="font-size: 9px; font-weight: bold; color: #1e40af; margin-bottom: 4px;">TASK 1 BAND SCORE</div>
                                <div style="font-size: 20px; font-weight: bold; color: #1e40af;">{{ $result->writing['task1_band'] }}</div>
                            </div>
                        </div>
                    @endif

                    @if(isset($result->writing['task2_band']))
                        <div style="display: table-cell; width: 50%; padding-left: 6px;">
                            <div style="background: #fae8ff; border: 2px solid #a855f7; padding: 10px; border-radius: 6px; text-align: center;">
                                <div style="font-size: 9px; font-weight: bold; color: #7e22ce; margin-bottom: 4px;">TASK 2 BAND SCORE</div>
                                <div style="font-size: 20px; font-weight: bold; color: #7e22ce;">{{ $result->writing['task2_band'] }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Detailed Scoring Criteria -->
        @if(isset($result->writing['task1_ta']) || isset($result->writing['task2_ta']))
            <div style="background: #f8fafc; padding: 12px; margin-bottom: 12px; border: 1px solid #e5e7eb; border-radius: 6px;">
                <div style="font-size: 10px; font-weight: bold; color: #1f2937; margin-bottom: 8px; text-align: center;">DETAILED SCORING BREAKDOWN</div>

                <div style="display: table; width: 100%;">
                    <!-- Task 1 Detailed Scores -->
                    @if(isset($result->writing['task1_ta']))
                        <div style="display: table-cell; width: 50%; padding-right: 6px; vertical-align: top;">
                            <div style="background: white; border: 1px solid #3b82f6; padding: 8px; border-radius: 4px;">
                                <div style="font-size: 8px; font-weight: bold; color: #1e40af; margin-bottom: 6px; text-align: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">TASK 1 CRITERIA</div>

                                <div style="margin-bottom: 4px;">
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Task Achievement (TA)</div>
                                    <div style="background: #dbeafe; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #1e40af;">{{ $result->writing['task1_ta'] ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <div style="margin-bottom: 4px;">
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Coherence & Cohesion (CC)</div>
                                    <div style="background: #dbeafe; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #1e40af;">{{ $result->writing['task1_cc'] ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <div style="margin-bottom: 4px;">
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Lexical Resource (LR)</div>
                                    <div style="background: #dbeafe; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #1e40af;">{{ $result->writing['task1_lr'] ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Grammatical Range & Accuracy (GRA)</div>
                                    <div style="background: #dbeafe; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #1e40af;">{{ $result->writing['task1_gra'] ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Task 2 Detailed Scores -->
                    @if(isset($result->writing['task2_ta']))
                        <div style="display: table-cell; width: 50%; padding-left: 6px; vertical-align: top;">
                            <div style="background: white; border: 1px solid #a855f7; padding: 8px; border-radius: 4px;">
                                <div style="font-size: 8px; font-weight: bold; color: #7e22ce; margin-bottom: 6px; text-align: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">TASK 2 CRITERIA</div>

                                <div style="margin-bottom: 4px;">
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Task Achievement (TA)</div>
                                    <div style="background: #fae8ff; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #7e22ce;">{{ $result->writing['task2_ta'] ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <div style="margin-bottom: 4px;">
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Coherence & Cohesion (CC)</div>
                                    <div style="background: #fae8ff; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #7e22ce;">{{ $result->writing['task2_cc'] ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <div style="margin-bottom: 4px;">
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Lexical Resource (LR)</div>
                                    <div style="background: #fae8ff; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #7e22ce;">{{ $result->writing['task2_lr'] ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <div style="font-size: 7px; color: #64748b; margin-bottom: 2px;">Grammatical Range & Accuracy (GRA)</div>
                                    <div style="background: #fae8ff; padding: 3px 6px; border-radius: 3px; text-align: center;">
                                        <span style="font-size: 9px; font-weight: bold; color: #7e22ce;">{{ $result->writing['task2_gra'] ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Task 1 Feedback -->
        @if(isset($result->writing['task1_feedback']))
            <div class="feedback-box" style="border-left: 4px solid #3b82f6;">
                <div class="label" style="color: #1e40af;">Task 1 Feedback</div>
                <div class="text" style="color: #1e3a8a;">{{ $result->writing['task1_feedback'] }}</div>
            </div>
        @endif

        <!-- Task 2 Feedback -->
        @if(isset($result->writing['task2_feedback']))
            <div class="feedback-box" style="border-left: 4px solid #a855f7;">
                <div class="label" style="color: #7e22ce;">Task 2 Feedback</div>
                <div class="text" style="color: #6b21a8;">{{ $result->writing['task2_feedback'] }}</div>
            </div>
        @endif

        <!-- General Feedback -->
        @if(isset($result->writing['teacher_feedback']))
            <div class="feedback-box">
                <div class="label">General Feedback</div>
                <div class="text">{{ $result->writing['teacher_feedback'] }}</div>
            </div>
        @endif

        @php
            $writingTasks = [];
            foreach($result->writing as $key => $value) {
                if (is_numeric($key)) {
                    $writingTasks[] = $value;
                }
            }
        @endphp

        @foreach($writingTasks as $index => $task)
            <div class="writing-container">
                <div class="writing-task-title">Task {{ $index + 1 }}</div>

                @if(isset($task['q']))
                    <div class="question-box">
                        <div class="label">Question</div>
                        <div class="text">{{ $task['q'] }}</div>
                    </div>
                @endif

                @if(isset($task['ans']))
                    <div class="answer-box">
                        <div class="label">Your Answer</div>
                        <div class="text">{{ $task['ans'] }}</div>
                    </div>
                @else
                    <p style="text-align: center; color: #6b7280; font-style: italic; padding: 12px; font-size: 9px;">No answer provided</p>
                @endif
            </div>
        @endforeach
    </div>
@endif

<!-- SPEAKING PAGE (if included in modules) -->
@if(in_array('speaking', $result->modules ?? []) && $result->speaking)
    <!-- Page Break before Speaking -->
    <div class="page-break"></div>
    <div class="page-container">
        <!-- Speaking Header -->
        <div class="page-header">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; vertical-align: middle; width: 35%;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af; margin-bottom: 6px;">SPEAKING SECTION</div>
                    <div style="font-size: 9px; color: #64748b; line-height: 1.5;">
                        <div><strong style="color: #1f2937;">Name:</strong> {{ $result->username }}</div>
                        @if($result->phone)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Phone:</strong> {{ $result->phone }}</div>
                        @endif
                        @if($result->email)
                            <div style="margin-top: 2px;"><strong style="color: #1f2937;">Email:</strong> {{ $result->email }}</div>
                        @endif
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: center; width: 30%;">
                    <div class="header-logo">
                        <img src="{{ public_path('logo.png') }}" alt="English Therapy">
                    </div>
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: right; width: 35%;">
                    @if(isset($result->speaking['band_score']))
                        <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; padding: 10px 18px; border-radius: 8px; display: inline-block;">
                            <div style="font-size: 8px; font-weight: 600; letter-spacing: 0.5px;">BAND SCORE</div>
                            <div style="font-size: 24px; font-weight: bold; line-height: 1;">{{ $result->speaking['band_score'] }}</div>
                        </div>
                    @else
                        <div style="color: #64748b; font-size: 9px; font-style: italic;">Assessment pending</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Speaking Content -->
        @if(isset($result->speaking['teacher_feedback']))
            <div class="feedback-box">
                <div class="label">Teacher Feedback</div>
                <div class="text">{{ $result->speaking['teacher_feedback'] }}</div>
            </div>
        @else
            <p style="text-align: center; color: #6b7280; font-style: italic; padding: 12px; font-size: 9px;">Assessment pending - teacher feedback will be provided soon.</p>
        @endif
    </div>
@endif
</body>
</html>
