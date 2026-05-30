# IELTS Mock Test Application - Deployment Guide

## Quick Start (Development)

### Prerequisites
- PHP 8.3+
- Node.js 18+
- Composer
- Laravel Herd (for local development)

### Setup Steps

1. **Install Dependencies**
```bash
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

4. **Build Assets**
```bash
npm run build
# OR for development with hot reload:
npm run dev
```

5. **Access Application**
Your app will be available at: https://ielts-mock-app.test (via Laravel Herd)

## Test Data Overview

The application comes pre-seeded with:
- **41 test users** from various countries (Spain, Japan, India, etc.)
- **10 complete IELTS tests** with all 4 sections
- **75+ test attempts** with realistic scores and rankings
- **1 admin user**: admin@ielts.test (password: password)

## User Accounts for Testing

### Admin Account
- **Email**: admin@ielts.test
- **Password**: password
- **Access**: Full admin dashboard, user management, test creation

### Sample Student Accounts
- **Email**: maria.gonzalez@email.com (Spain, Target: 7.0)
- **Email**: hiroshi.tanaka@email.com (Japan, Target: 6.5)
- **Email**: priya.sharma@email.com (India, Target: 8.0)
- **Password**: password (for all accounts)

## Production Deployment

### Environment Variables (.env)
```env
APP_NAME="IELTS Mock Test"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ielts_production
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

### Production Commands
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Creating Your First Admin User

```bash
php artisan tinker
```

```php
User::create([
    'name' => 'Your Name',
    'email' => 'your-admin@domain.com',
    'password' => Hash::make('your-secure-password'),
    'role' => 'admin',
    'country' => 'Your Country',
    'email_verified_at' => now(),
]);
```

## Application Features Overview

### For Students
- **Registration** with IELTS-specific fields (phone, passport, country, target band)
- **Mock Tests** for all 4 IELTS sections with realistic timing
- **Auto-scoring** for Listening & Reading sections
- **Performance Analytics** with detailed band breakdowns
- **Global Leaderboard** with country-based rankings
- **Progress Tracking** across multiple test attempts

### For Administrators
- **User Management** with detailed student profiles
- **Test Creation** with JSON-based content structure
- **Score Management** for Writing & Speaking sections
- **Analytics Dashboard** with system-wide statistics
- **Content Management** for all test materials

## Test Content Structure

Tests are stored as JSON with this structure:

```json
{
  "listening_content": {
    "sections": [
      {
        "title": "Section 1: Social Situations",
        "audio_url": "/audio/test1_section1.mp3",
        "questions": [
          {
            "number": 1,
            "type": "fill_blank",
            "question": "The caller's name is ______.",
            "correct_answer": "Sarah Johnson"
          }
        ]
      }
    ]
  },
  "reading_content": {
    "passages": [
      {
        "title": "Climate Change Impact",
        "text": "Full passage text here...",
        "questions": [
          {
            "number": 1,
            "type": "multiple_choice",
            "question": "What is the main cause of climate change?",
            "options": ["A) Solar radiation", "B) Human activities", "C) Natural cycles"],
            "correct_answer": "B"
          }
        ]
      }
    ]
  }
}
```

## Customization Options

### Adding New Question Types
Extend the scoring system in `AttemptController.php`:

```php
private function checkAnswer(array $question, string $userAnswer): bool
{
    switch ($question['type']) {
        case 'multiple_choice':
            return strtoupper(trim($userAnswer)) === strtoupper($question['correct_answer']);
        case 'true_false':
            return strtolower(trim($userAnswer)) === strtolower($question['correct_answer']);
        case 'your_new_type':
            // Add your custom logic here
            return $this->customScoringLogic($question, $userAnswer);
    }
}
```

### Modifying Band Score Calculations
Update the conversion logic in `AttemptController.php`:

```php
private function convertToIeltsBand(int $correctAnswers, int $totalQuestions): float
{
    if ($totalQuestions === 0) return 0;
    $percentage = ($correctAnswers / $totalQuestions) * 100;

    // Customize these thresholds based on your requirements
    if ($percentage >= 89) return 9.0;
    if ($percentage >= 82) return 8.5;
    // ... add your custom band calculations
}
```

### Adding New User Fields
1. Create migration:
```bash
php artisan make:migration add_custom_fields_to_users_table
```

2. Update User model with new fillable fields and relationships

3. Update registration form in `resources/js/Pages/auth/Register.vue`

## Performance Optimization

### Database Indexes
Add indexes for frequently queried fields:

```sql
CREATE INDEX idx_test_attempts_user_id ON test_attempts(user_id);
CREATE INDEX idx_test_attempts_overall_band ON test_attempts(overall_band);
CREATE INDEX idx_user_answers_attempt_section ON user_answers(attempt_id, section);
```

### Caching Strategy
Implement caching for leaderboards and statistics:

```php
// In your controller
$leaderboard = Cache::remember('global_leaderboard', 3600, function () {
    return TestAttempt::with('user')
        ->whereNotNull('overall_band')
        ->orderBy('overall_band', 'desc')
        ->take(100)
        ->get();
});
```

## Security Considerations

### Rate Limiting
Add rate limiting to test routes in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleWithRedis();
    $middleware->group('test', [
        'throttle:test-attempts,1,60', // 1 attempt per minute
    ]);
})
```

### Input Validation
Ensure all test inputs are properly validated:

```php
// In your form request
public function rules(): array
{
    return [
        'answers' => 'required|array',
        'answers.*' => 'required|string|max:1000',
        'section' => 'required|in:listening,reading,writing,speaking',
    ];
}
```

## Monitoring & Analytics

### Error Tracking
Monitor application errors and performance:

```php
// Add to bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (Throwable $e) {
        // Send to your monitoring service
        // e.g., Sentry, Bugsnag, etc.
    });
})
```

### User Analytics
Track user engagement and test completion rates:

```php
// Add events for analytics
Event::dispatch(new TestStarted($user, $test));
Event::dispatch(new TestCompleted($attempt));
Event::dispatch(new SectionCompleted($attempt, $section));
```

## Support & Maintenance

### Regular Maintenance Tasks
```bash
# Weekly maintenance
php artisan queue:prune-batches --hours=48
php artisan model:prune --model=App\\Models\\UserSession

# Monthly maintenance
php artisan telescope:prune --hours=720
```

### Backup Strategy
```bash
# Database backup
php artisan backup:run --only-db

# Full application backup
php artisan backup:run
```

## Next Steps

1. **Customize branding** - Update colors, logos, and styling in Tailwind config
2. **Add payment integration** - Implement Stripe/PayPal for premium tests
3. **Mobile app** - Use Laravel Sanctum API for mobile app development
4. **Advanced analytics** - Add detailed performance insights and recommendations
5. **Multi-language support** - Implement Laravel localization for international users

Your IELTS Mock Test application is now ready for production deployment!