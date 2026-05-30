<?php

/**
 * Practice Limit Verification Script
 *
 * Run this in tinker to verify the subscription system is working:
 * php artisan tinker
 * require 'verify-practice-limits.php';
 */

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;

echo "\n=== Practice Limit Verification ===\n\n";

// 1. Create test package
echo "1. Creating test package...\n";
$package = SubscriptionPackage::create([
    'name' => 'Test Practice Package',
    'duration' => 30,
    'price' => 1000,
    'discount' => 0,
    'discount_type' => 'flat',
    'is_active' => true,
    'full_mock_test_limit' => 5,
    'partial_reading_limit' => 10,
    'partial_writing_limit' => 10,
    'partial_listening_limit' => 10,
    'partial_speaking_limit' => 10,
    'practice_reading_enabled' => true,
    'practice_listening_enabled' => true,
    'practice_writing_enabled' => true,
    'practice_writing_limit' => 3,
    'practice_speaking_enabled' => true,
    'practice_speaking_limit' => 5,
]);
echo "   ✓ Package created: {$package->name} (ID: {$package->id})\n";

// 2. Find or create test student
echo "\n2. Finding/creating test student...\n";
$student = User::where('email', 'test-student@example.com')->first();
if (!$student) {
    $student = User::create([
        'name' => 'Test Student',
        'email' => 'test-student@example.com',
        'password' => bcrypt('password'),
        'role' => 'student',
    ]);
    echo "   ✓ Student created: {$student->name}\n";
} else {
    echo "   ✓ Student found: {$student->name}\n";
}

// 3. Delete existing subscriptions
echo "\n3. Cleaning up old subscriptions...\n";
UserSubscription::where('user_id', $student->id)->delete();
echo "   ✓ Old subscriptions removed\n";

// 4. Create subscription
echo "\n4. Creating subscription...\n";
$subscription = UserSubscription::create([
    'user_id' => $student->id,
    'package_id' => $package->id,
    'subscribed_at' => now(),
    'expires_at' => now()->addDays(30),
    'is_active' => true,
    'full_mock_tests_used' => 0,
    'partial_reading_used' => 0,
    'partial_writing_used' => 0,
    'partial_listening_used' => 0,
    'partial_speaking_used' => 0,
    'practice_writing_used' => 0,
    'practice_speaking_used' => 0,
]);
echo "   ✓ Subscription created (ID: {$subscription->id})\n";

// 5. Test subscription methods
echo "\n5. Testing subscription methods...\n";
$student->refresh();

echo "   Active subscription: " . ($student->hasActiveSubscription() ? "✓ Yes" : "✗ No") . "\n";
echo "   Subscription expired: " . ($student->isSubscriptionExpired() ? "✗ Yes" : "✓ No") . "\n";

// 6. Test writing practice access
echo "\n6. Testing writing practice access...\n";
$canAccess = $student->canAccessResource('practice_writing');
$remaining = $student->getRemainingWritingPractice();
echo "   Can access: " . ($canAccess ? "✓ Yes" : "✗ No") . "\n";
echo "   Remaining: {$remaining} / {$package->practice_writing_limit}\n";

// 7. Simulate usage
echo "\n7. Simulating writing practice usage...\n";
echo "   Initial: {$subscription->practice_writing_used} / {$package->practice_writing_limit}\n";

$student->incrementUsage('practice_writing');
$subscription->refresh();
echo "   After 1st use: {$subscription->practice_writing_used} / {$package->practice_writing_limit}\n";

$student->incrementUsage('practice_writing');
$subscription->refresh();
echo "   After 2nd use: {$subscription->practice_writing_used} / {$package->practice_writing_limit}\n";

$student->incrementUsage('practice_writing');
$subscription->refresh();
echo "   After 3rd use: {$subscription->practice_writing_used} / {$package->practice_writing_limit}\n";

// 8. Test access after limit reached
echo "\n8. Testing access after limit reached...\n";
$student->refresh();
$canAccessNow = $student->canAccessResource('practice_writing');
$remainingNow = $student->getRemainingWritingPractice();
echo "   Can access: " . ($canAccessNow ? "✗ Yes (SHOULD BE NO!)" : "✓ No") . "\n";
echo "   Remaining: {$remainingNow} / {$package->practice_writing_limit}\n";

// 9. Test speaking practice
echo "\n9. Testing speaking practice...\n";
$canAccessSpeaking = $student->canAccessResource('practice_speaking');
$remainingSpeaking = $student->getRemainingSpeakingPractice();
echo "   Can access: " . ($canAccessSpeaking ? "✓ Yes" : "✗ No") . "\n";
echo "   Remaining: {$remainingSpeaking} / {$package->practice_speaking_limit}\n";

// 10. Summary
echo "\n=== Summary ===\n";
echo "Package: {$package->name}\n";
echo "Student: {$student->name} ({$student->email})\n";
echo "Subscription Status: " . ($subscription->isActive() ? "Active" : "Inactive") . "\n";
echo "Expires: " . $subscription->expires_at->format('Y-m-d H:i:s') . "\n";
echo "Days Remaining: {$subscription->days_remaining}\n";
echo "\nUsage:\n";
echo "  Writing: {$subscription->practice_writing_used} / {$package->practice_writing_limit}\n";
echo "  Speaking: {$subscription->practice_speaking_used} / {$package->practice_speaking_limit}\n";

echo "\n=== Test Complete ===\n";
echo "\nYou can now:\n";
echo "1. Login as: {$student->email} / password\n";
echo "2. Visit: https://englishtherapy-ielts.test/student/dashboard\n";
echo "3. Try writing practice (should be blocked): https://englishtherapy-ielts.test/writing\n";
echo "4. Try speaking practice (should work): https://englishtherapy-ielts.test/speaking\n\n";

echo "To cleanup:\n";
echo "  \$package = \\App\\Models\\SubscriptionPackage::find({$package->id});\n";
echo "  \$package->delete();\n";
echo "  \$student = \\App\\Models\\User::find({$student->id});\n";
echo "  \$student->delete();\n\n";
