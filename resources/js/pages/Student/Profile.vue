<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Building, Calendar, Globe, Hash, Mail, Phone, Target, User } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth?.user as any;

const profileFields = [
    { label: 'Full Name', value: user?.name, icon: User },
    { label: 'Email Address', value: user?.email, icon: Mail },
    { label: 'Phone Number', value: user?.phone || 'Not provided', icon: Phone },
    { label: 'Roll Number', value: user?.roll_number || 'Not assigned', icon: Hash },
    { label: 'Institute', value: user?.institute || 'Not specified', icon: Building },
    { label: 'Passport Number', value: user?.passport || 'Not provided', icon: Hash },
    { label: 'Country', value: user?.country || 'Not specified', icon: Globe },
    { label: 'Target Band Score', value: user?.target_band ? `Band ${user.target_band}` : 'Not set', icon: Target },
];

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="My Profile" />

    <StudentLayout title="My Profile">
        <div class="w-full">
            <!-- Profile Header Card -->
            <div class="mb-6 overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="bg-black px-8 py-10">
                    <div class="flex items-center gap-6">
                        <div class="flex h-24 w-24 items-center justify-center bg-white/20">
                            <span class="text-4xl font-bold text-white">
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </span>
                        </div>
                        <div class="text-white">
                            <h2 class="mb-1 text-2xl font-bold">{{ user?.name }}</h2>
                            <p class="flex items-center gap-2 text-gray-300">
                                <Mail class="h-4 w-4" />
                                {{ user?.email }}
                            </p>
                            <div class="mt-3 flex items-center gap-4">
                                <span v-if="user?.roll_number" class="inline-flex items-center gap-1.5 bg-white/20 px-3 py-1 text-sm">
                                    <Hash class="h-3.5 w-3.5" />
                                    {{ user.roll_number }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 bg-white/20 px-3 py-1 text-sm">
                                    <Calendar class="h-3.5 w-3.5" />
                                    Member since {{ formatDate(user?.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Details -->
            <div class="overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Profile Details</h3>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="field in profileFields" :key="field.label" class="flex items-center gap-4 border border-gray-100 bg-gray-50 p-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-gray-100">
                            <component :is="field.icon" class="h-5 w-5 text-gray-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-500">{{ field.label }}</p>
                            <p class="truncate font-medium text-gray-900">{{ field.value }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Note -->
            <div class="mt-6 border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm text-gray-700"><strong>Note:</strong> To update your profile information, please contact the administrator.</p>
            </div>
        </div>
    </StudentLayout>
</template>
