<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { Award, CreditCard, ExternalLink, FileIcon, FileText, LogOut, Network, Package, Receipt, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: FileIcon,
    },
    {
        title: 'Exams',
        href: '/exams',
        icon: FileText,
    },
    {
        title: 'Results',
        href: '/results',
        icon: Award,
    },
    {
        title: 'Students',
        href: '/students',
        icon: Users,
    },
    // {
    //     title: 'Coupons',
    //     href: '/coupons',
    //     icon: Ticket,
    // },
    {
        title: 'Subscriptions',
        href: '/subscriptions/packages',
        icon: Package,
    },
    {
        title: 'Payment History',
        href: '/payment-history',
        icon: CreditCard,
    },
    {
        title: 'Billing',
        href: '/billing',
        icon: Receipt,
    },
    {
        title: 'Coaching IPs',
        href: '/settings/coaching-ips',
        icon: Network,
    },
    {
        title: 'Student Portal',
        href: '/student/dashboard',
        icon: ExternalLink,
    },
];

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" @click="handleLogout" class="cursor-pointer border hover:bg-gray-100">
                        <div class="flex items-center gap-3 text-gray-700">
                            <div class="flex h-8 w-8 items-center justify-center bg-gray-200">
                                <LogOut class="h-4 w-4" />
                            </div>
                            <span class="text-sm font-semibold">Logout</span>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
