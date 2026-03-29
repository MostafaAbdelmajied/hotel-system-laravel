<script lang="ts" setup>
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, CalendarDays, DoorOpen, FolderGit2, Layers, LayoutGrid, UserCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (page.props.auth?.canViewPendingClients) {
        items.push({
            title: 'Pending Clients',
            href: '/clients/pending',
            icon: Users,
        });
    }

    if (page.props.auth?.canViewMyApprovedClients) {
        items.push({
            title: page.props.auth?.isAdmin
                ? 'Approved Clients'
                : 'My Approved Clients',
            href: '/clients/my-approved',
            icon: UserCheck,
        });
    }

    if (page.props.auth?.canViewReservations) {
        items.push({
            title: 'Reservations',
            href: '/bookings/available-rooms',
            icon: CalendarDays,
        });
    }

    if (page.props.auth?.isManager || page.props.auth?.isAdmin) {
        items.push({
            title: 'Manage Floors',
            href: '/manager/floors',
            icon: Layers,
        });
        items.push({
            title: 'Manage Rooms',
            href: '/manager/rooms',
            icon: DoorOpen,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
