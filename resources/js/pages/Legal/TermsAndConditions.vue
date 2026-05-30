<script setup lang="ts">
import AppFooter from '@/components/Common/AppFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Terms and Conditions" />

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
                <h1 class="mb-8 text-3xl font-bold text-gray-900">Terms and Conditions</h1>
                <p class="mb-6 text-sm text-gray-500">
                    Last updated: {{ new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>

                <div class="prose prose-gray max-w-none">
                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">1. Acceptance of Terms</h2>
                        <p class="text-gray-600">
                            By accessing and using the IELTS Mock Test platform, you agree to be bound by these Terms and Conditions. If you do not
                            agree to these terms, please do not use our services.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">2. Use of Service</h2>
                        <p class="mb-4 text-gray-600">
                            You agree to use our platform only for lawful purposes and in accordance with these Terms. You agree not to:
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-gray-600">
                            <li>Use the service for any illegal or unauthorized purpose</li>
                            <li>Reproduce, distribute, or transmit any content without authorization</li>
                            <li>Attempt to interfere with the proper working of the platform</li>
                            <li>Share your account credentials with others</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">3. User Accounts</h2>
                        <p class="text-gray-600">
                            When you create an account, you are responsible for maintaining the confidentiality of your account information and for
                            all activities that occur under your account. You must notify us immediately of any unauthorized use.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">4. Intellectual Property</h2>
                        <p class="text-gray-600">
                            All content on this platform, including but not limited to text, graphics, logos, and test materials, is the property of
                            Nextive Solution and is protected by intellectual property laws.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">5. Disclaimer</h2>
                        <p class="text-gray-600">
                            This platform is an independent IELTS preparation tool and is not affiliated with, endorsed by, or officially connected to
                            the British Council, IDP, or Cambridge Assessment English.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">6. Limitation of Liability</h2>
                        <p class="text-gray-600">
                            Nextive Solution shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting
                            from your use of or inability to use the service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">7. Changes to Terms</h2>
                        <p class="text-gray-600">
                            We reserve the right to modify these terms at any time. Continued use of the platform after any modifications indicates
                            your acceptance of the updated terms.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">8. Contact Information</h2>
                        <p class="mb-4 text-gray-600">For any questions regarding these Terms and Conditions, please contact us:</p>
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
