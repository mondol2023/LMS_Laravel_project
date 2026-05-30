<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-gray-200 bg-gray-50 py-8 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
        <div class="container mx-auto px-4">
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <!-- Company Info -->
                <div>
                    <div class="mb-4 flex items-center gap-3">
                        <img src="/logo.png" alt="English Therapy" class="h-10 w-10 rounded-lg object-contain" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">English Therapy</h3>
                    </div>
                    <div class="space-y-2 text-sm">
                        <p class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>House#32, Road#01, Metropolitan Co-operative Housing Society, Babar Rd, Dhaka 1207</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            <a href="mailto:nextivesolution@gmail.com" class="transition-colors hover:text-blue-500">nextivesolution@gmail.com</a>
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                />
                            </svg>
                            <a href="tel:01515287150" class="transition-colors hover:text-blue-500">01515287150</a>
                        </p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <Link href="/terms-and-conditions" class="transition-colors hover:text-blue-500">Terms and Conditions</Link>
                        </li>
                        <li>
                            <Link href="/privacy-policy" class="transition-colors hover:text-blue-500">Privacy Policy</Link>
                        </li>
                        <li>
                            <a
                                href="https://www.nextivesolution.com/contact"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="transition-colors hover:text-blue-500"
                                >Contact Us</a
                            >
                        </li>
                    </ul>
                </div>

                <!-- Developer Info -->
                <div>
                    <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Developed By</h4>
                    <a
                        href="https://www.nextivesolution.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Nextive Solution
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Copyright -->
            <div class="mt-8 border-t border-gray-200 pt-6 text-center text-sm dark:border-gray-700">
                <p>&copy; {{ currentYear }} Nextive Solution. All rights reserved.</p>
            </div>
        </div>
    </footer>
</template>
