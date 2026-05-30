<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="text-xl font-bold text-gray-900">IELTS Mock Test</Link>
                    <Link href="/" class="text-sm font-medium text-blue-600 hover:text-blue-700">Back to Home</Link>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <h1 class="mb-8 text-3xl font-bold text-gray-900">Privacy Policy</h1>
                <p class="mb-6 text-sm text-gray-500">
                    Last updated: {{ new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>

                <div class="prose prose-gray max-w-none">
                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">1. Introduction</h2>
                        <p class="text-gray-600">
                            Nextive Solution ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we
                            collect, use, disclose, and safeguard your information when you use the IELTS Mock Test platform.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">2. Information We Collect</h2>
                        <p class="mb-4 text-gray-600">We may collect information about you in various ways, including:</p>
                        <ul class="list-disc space-y-2 pl-6 text-gray-600">
                            <li>
                                <strong>Personal Data:</strong> Name, email address, phone number, and other contact information you provide during
                                registration
                            </li>
                            <li>
                                <strong>Usage Data:</strong> Information about how you use our platform, including test scores, practice history, and
                                session data
                            </li>
                            <li><strong>Device Information:</strong> Browser type, IP address, and device identifiers</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">3. How We Use Your Information</h2>
                        <p class="mb-4 text-gray-600">We use the collected information for various purposes:</p>
                        <ul class="list-disc space-y-2 pl-6 text-gray-600">
                            <li>To provide and maintain our service</li>
                            <li>To personalize your learning experience</li>
                            <li>To track your progress and provide performance analytics</li>
                            <li>To communicate with you about updates and support</li>
                            <li>To improve our platform and develop new features</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">4. Data Security</h2>
                        <p class="text-gray-600">
                            We implement appropriate technical and organizational security measures to protect your personal information. However, no
                            method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">5. Data Sharing</h2>
                        <p class="text-gray-600">
                            We do not sell, trade, or rent your personal information to third parties. We may share your information only with service
                            providers who assist us in operating our platform, subject to confidentiality agreements.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">6. Cookies</h2>
                        <p class="text-gray-600">
                            Our platform uses cookies to enhance your experience. Cookies are small data files stored on your device. You can control
                            cookie settings through your browser, but disabling cookies may affect platform functionality.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">7. Your Rights</h2>
                        <p class="mb-4 text-gray-600">You have the right to:</p>
                        <ul class="list-disc space-y-2 pl-6 text-gray-600">
                            <li>Access and receive a copy of your personal data</li>
                            <li>Request correction of inaccurate data</li>
                            <li>Request deletion of your personal data</li>
                            <li>Opt-out of marketing communications</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">8. Children's Privacy</h2>
                        <p class="text-gray-600">
                            Our service is intended for users who are at least 13 years old. We do not knowingly collect personal information from
                            children under 13 without parental consent.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">9. Changes to This Policy</h2>
                        <p class="text-gray-600">
                            We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy
                            on this page and updating the "Last updated" date.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">10. Contact Us</h2>
                        <p class="mb-4 text-gray-600">If you have any questions about this Privacy Policy, please contact us:</p>
                        <div class="rounded-lg bg-gray-50 p-4 text-gray-600">
                            <p><strong>Nextive Solution</strong></p>
                            <p>House#32, Road#01, Metropolitan Co-operative Housing Society, Babar Rd, Dhaka 1207</p>
                            <p>
                                Email: <a href="mailto:nextivesolution@gmail.com" class="text-blue-600 hover:underline">nextivesolution@gmail.com</a>
                            </p>
                            <p>Phone: <a href="tel:01515287150" class="text-blue-600 hover:underline">01515287150</a></p>
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <AppFooter />
    </div>
</template>
