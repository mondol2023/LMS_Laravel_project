# IELTS Mock Test API Documentation

## Overview

This API provides access to the IELTS Mock Test application features for mobile app integration. All endpoints require authentication using Laravel Sanctum tokens.

## Authentication

### Register User
```http
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "passport": "AB123456",
  "country": "United States",
  "target_band": 7.0
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "country": "United States",
    "target_band": 7.0,
    "role": "student"
  },
  "token": "1|abc123def456...",
  "message": "User registered successfully"
}
```

### Login
```http
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "country": "United States",
    "role": "student"
  },
  "token": "2|xyz789abc123...",
  "message": "Login successful"
}
```

### Logout
```http
POST /api/v1/logout
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

## User Management

### Get User Profile
```http
GET /api/v1/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "passport": "AB123456",
    "country": "United States",
    "target_band": 7.0,
    "role": "student",
    "email_verified_at": "2024-01-15T10:30:00Z",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Update User Profile
```http
PUT /api/v1/user
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Smith",
  "phone": "+1234567891",
  "country": "Canada",
  "target_band": 8.0
}
```

### Get User Statistics
```http
GET /api/v1/user/stats
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "total_attempts": 5,
    "completed_attempts": 3,
    "best_score": 7.5,
    "average_score": 6.8,
    "current_rank": 15,
    "sections": {
      "listening": {
        "best_score": 8.0,
        "average_score": 7.2,
        "attempts": 3
      },
      "reading": {
        "best_score": 7.5,
        "average_score": 6.8,
        "attempts": 3
      },
      "writing": {
        "best_score": 6.5,
        "average_score": 6.0,
        "attempts": 2
      },
      "speaking": {
        "best_score": 7.0,
        "average_score": 6.5,
        "attempts": 2
      }
    }
  }
}
```

## Tests

### Get Available Tests
```http
GET /api/v1/tests
Authorization: Bearer {token}
```

**Query Parameters:**
- `type` (optional): `academic` or `general_training`
- `difficulty` (optional): `easy`, `medium`, `hard`
- `page` (optional): Page number for pagination

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "IELTS Academic Practice Test 1",
      "type": "academic",
      "difficulty": "medium",
      "listening_sections": 4,
      "reading_passages": 3,
      "writing_tasks": 2,
      "speaking_parts": 3,
      "user_attempts": 2,
      "best_score": 7.0,
      "has_active_attempt": false
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25
  }
}
```

### Get Test Details
```http
GET /api/v1/tests/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "title": "IELTS Academic Practice Test 1",
    "type": "academic",
    "difficulty": "medium",
    "listening_sections": 4,
    "reading_passages": 3,
    "writing_tasks": 2,
    "speaking_parts": 3,
    "structure": {
      "listening": {
        "duration": 2400,
        "sections": 4,
        "questions": 40
      },
      "reading": {
        "duration": 3600,
        "passages": 3,
        "questions": 40
      },
      "writing": {
        "duration": 3600,
        "tasks": 2,
        "task1_words": 150,
        "task2_words": 250
      },
      "speaking": {
        "duration": 900,
        "parts": 3
      }
    },
    "user_attempts": [
      {
        "id": 1,
        "attempt_number": 1,
        "status": "completed",
        "overall_band": 7.0,
        "started_at": "2024-01-10T09:00:00Z",
        "completed_at": "2024-01-10T12:30:00Z"
      }
    ]
  }
}
```

### Start Test Attempt
```http
POST /api/v1/tests/{id}/start
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "attempt_id": 15,
    "test_id": 1,
    "status": "in_progress",
    "current_section": "listening",
    "started_at": "2024-01-15T14:00:00Z",
    "time_spent": {
      "listening": 0,
      "reading": 0,
      "writing": 0,
      "speaking": 0
    }
  },
  "message": "Test attempt started successfully"
}
```

## Test Taking

### Get Test Content
```http
GET /api/v1/attempts/{attempt_id}/section/{section}
Authorization: Bearer {token}
```

**Sections:** `listening`, `reading`, `writing`, `speaking`

**Response (Listening Example):**
```json
{
  "data": {
    "section": "listening",
    "duration": 2400,
    "instructions": "You will hear a number of different recordings...",
    "content": {
      "sections": [
        {
          "title": "Section 1: Social Situations",
          "audio_url": "https://your-domain.com/audio/test1_section1.mp3",
          "questions": [
            {
              "number": 1,
              "type": "fill_blank",
              "question": "The caller's name is ______.",
              "options": null
            },
            {
              "number": 2,
              "type": "multiple_choice",
              "question": "What time is the appointment?",
              "options": ["A) 2:00 PM", "B) 3:00 PM", "C) 4:00 PM"]
            }
          ]
        }
      ]
    },
    "current_answers": {
      "1": "Sarah Johnson",
      "2": "B"
    },
    "time_spent": 420
  }
}
```

### Submit Answers
```http
POST /api/v1/attempts/{attempt_id}/answers
Authorization: Bearer {token}
Content-Type: application/json

{
  "section": "listening",
  "answers": {
    "1": "Sarah Johnson",
    "2": "B",
    "3": "library",
    "4": "Monday"
  },
  "time_spent": 1200
}
```

**Response:**
```json
{
  "message": "Answers submitted successfully",
  "data": {
    "section": "listening",
    "questions_answered": 4,
    "time_spent": 1200,
    "auto_graded": true,
    "score": 8.0
  }
}
```

### Complete Section
```http
POST /api/v1/attempts/{attempt_id}/complete-section
Authorization: Bearer {token}
Content-Type: application/json

{
  "section": "listening"
}
```

### Submit Complete Test
```http
POST /api/v1/attempts/{attempt_id}/submit
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "attempt_id": 15,
    "status": "completed",
    "overall_band": 7.5,
    "section_scores": {
      "listening": 8.0,
      "reading": 7.5,
      "writing": null,
      "speaking": null
    },
    "rank": 12,
    "completed_at": "2024-01-15T17:30:00Z"
  },
  "message": "Test submitted successfully"
}
```

## Results & Analytics

### Get Test Results
```http
GET /api/v1/attempts/{attempt_id}/results
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "attempt": {
      "id": 15,
      "test_title": "IELTS Academic Practice Test 1",
      "status": "completed",
      "overall_band": 7.5,
      "listening_score": 8.0,
      "reading_score": 7.5,
      "writing_score": null,
      "speaking_score": null,
      "time_spent": {
        "listening": 2100,
        "reading": 3300,
        "writing": 3400,
        "speaking": 780
      },
      "total_time": 9580,
      "rank": 12,
      "completed_at": "2024-01-15T17:30:00Z"
    },
    "section_details": {
      "listening": {
        "score": 8.0,
        "correct_answers": 35,
        "total_questions": 40,
        "accuracy": 87.5
      },
      "reading": {
        "score": 7.5,
        "correct_answers": 32,
        "total_questions": 40,
        "accuracy": 80.0
      }
    },
    "band_descriptions": {
      "overall": "Good User",
      "listening": "Very Good User",
      "reading": "Good User"
    }
  }
}
```

### Get User Attempts History
```http
GET /api/v1/user/attempts
Authorization: Bearer {token}
```

**Query Parameters:**
- `test_id` (optional): Filter by specific test
- `status` (optional): `completed`, `in_progress`, `abandoned`
- `page` (optional): Page number

**Response:**
```json
{
  "data": [
    {
      "id": 15,
      "test_id": 1,
      "test_title": "IELTS Academic Practice Test 1",
      "attempt_number": 2,
      "status": "completed",
      "overall_band": 7.5,
      "rank": 12,
      "started_at": "2024-01-15T14:00:00Z",
      "completed_at": "2024-01-15T17:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "total": 5
  }
}
```

## Leaderboard

### Get Global Leaderboard
```http
GET /api/v1/leaderboard
Authorization: Bearer {token}
```

**Query Parameters:**
- `test_id` (optional): Filter by specific test
- `type` (optional): `academic`, `general_training`, `overall`
- `country` (optional): Filter by country
- `page` (optional): Page number

**Response:**
```json
{
  "data": [
    {
      "rank": 1,
      "user_name": "Maria Gonzalez",
      "user_country": "Spain",
      "test_title": "IELTS Academic Practice Test 1",
      "test_type": "academic",
      "overall_band": 8.5,
      "completion_time": "3h 25m",
      "created_at": "2024-01-15T10:30:00Z",
      "is_current_user": false
    }
  ],
  "user_stats": {
    "current_rank": 12,
    "best_score": 7.5,
    "total_attempts": 5
  },
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

## Error Responses

All API endpoints may return the following error responses:

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "Access denied"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 429 Rate Limit
```json
{
  "message": "Too many requests"
}
```

### 500 Server Error
```json
{
  "message": "Internal server error"
}
```

## Rate Limiting

- **Authentication endpoints**: 5 requests per minute
- **General API endpoints**: 60 requests per minute
- **Test submission**: 1 request per minute
- **File uploads**: 10 requests per hour

## Webhooks (Optional)

Configure webhooks to receive real-time notifications:

### Test Completed
```json
{
  "event": "test.completed",
  "data": {
    "user_id": 1,
    "attempt_id": 15,
    "test_id": 1,
    "overall_band": 7.5,
    "rank": 12,
    "completed_at": "2024-01-15T17:30:00Z"
  }
}
```

### Score Updated
```json
{
  "event": "score.updated",
  "data": {
    "attempt_id": 15,
    "section": "writing",
    "score": 6.5,
    "graded_by": "admin@ielts.test",
    "updated_at": "2024-01-16T09:15:00Z"
  }
}
```

## SDK Examples

### JavaScript/React Native
```javascript
// Initialize API client
const api = new IELTSApiClient({
  baseURL: 'https://your-domain.com/api/v1',
  timeout: 30000
});

// Login
const response = await api.auth.login({
  email: 'user@example.com',
  password: 'password'
});

// Store token
await AsyncStorage.setItem('auth_token', response.token);

// Start test
const attempt = await api.tests.start(testId);

// Submit answers
await api.attempts.submitAnswers(attempt.id, {
  section: 'listening',
  answers: { '1': 'answer1', '2': 'answer2' }
});
```

### Flutter/Dart
```dart
// Initialize API client
final api = IELTSApiClient(
  baseUrl: 'https://your-domain.com/api/v1',
);

// Login
final response = await api.auth.login(
  email: 'user@example.com',
  password: 'password',
);

// Store token
await storage.write(key: 'auth_token', value: response.token);

// Start test
final attempt = await api.tests.start(testId);

// Submit answers
await api.attempts.submitAnswers(
  attempt.id,
  section: 'listening',
  answers: {'1': 'answer1', '2': 'answer2'},
);
```

## Testing

Use the following test credentials for API development:

**Test Student Account:**
- Email: `test.student@ielts.test`
- Password: `password`

**Test Admin Account:**
- Email: `test.admin@ielts.test`
- Password: `password`

**Test Endpoints:**
- Base URL: `https://ielts-mock-app.test/api/v1`
- Documentation: `https://ielts-mock-app.test/api/documentation`

## Support

For API support and questions:
- Documentation: `https://your-domain.com/api/documentation`
- Support Email: `api-support@your-domain.com`
- Status Page: `https://status.your-domain.com`