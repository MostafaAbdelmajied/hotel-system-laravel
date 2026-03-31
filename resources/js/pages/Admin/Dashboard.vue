<script lang="ts" setup>
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    CreditCard,
    DoorOpen,
    Layers3,
    ShieldCheck,
    TrendingUp,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import * as clientRoutes from '@/routes/clients';
import * as managerFloorRoutes from '@/routes/manager/floors';
import * as managerRoomRoutes from '@/routes/manager/rooms';
import type { Auth, BreadcrumbItem } from '@/types';

type PageProps = {
    auth: Auth;
};

type RecentActivity = {
    id: string;
    user: string;
    action: string;
    time: string;
    amount: string | null;
    positive: boolean;
};

type DashboardData = {
    pending_actions: {
        pending_clients: {
            count: number;
            href: NonNullable<InertiaLinkProps['href']>;
        };
    };
    system_overview: {
        total_clients: number;
        total_managers: number;
        total_receptionists: number;
    };
    quick_stats: {
        active_reservations: number;
        available_rooms: number;
        total_rooms: number;
        total_floors: number;
    };
    recent_activity: RecentActivity[];
};

type Props = {
    dashboardData: DashboardData;
};

type ToneClass = 'text-emerald-600 dark:text-emerald-400' | 'text-amber-600 dark:text-amber-400' | 'text-primary';

type HighlightCard = {
    label: string;
    value: string;
    note: string;
    toneClass: ToneClass;
    ringClass: string;
    icon: typeof Users;
    barPercent: number;
    barClass: string;
    panelClass: string;
    chipClass: string;
};

type FocusPanelItem = {
    label: string;
    value: string;
    hint: string;
    toneClass: ToneClass;
    dotClass: string;
};

type SummaryTile = {
    label: string;
    value: number | string;
    toneClass: ToneClass;
    panelClass: string;
};

type QuickStatTile = SummaryTile & {
    progress: number;
    progressClass: string;
    helper: string;
};

type NavigationTile = {
    label: string;
    description: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: typeof Users;
    toneClass: ToneClass;
    panelClass: string;
};

type ActionCenterItem = {
    label: string;
    value: string;
    note: string;
    href: NonNullable<InertiaLinkProps['href']>;
    cta: string;
    icon: typeof Users;
    toneClass: ToneClass;
    chipClass: string;
};

type OperationsChartItem = {
    label: string;
    value: number;
    count: number;
    toneClass: ToneClass;
    barClass: string;
    chipClass: string;
    helper: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const props = defineProps<Props>();
const page = usePage<PageProps>();
const currentUserName = computed(() => page.props.auth?.user?.name ?? 'Admin');
const pendingClientsRoute = clientRoutes.pending();
const approvedClientsRoute = clientRoutes.myApproved();
const floorsRoute = managerFloorRoutes.index();
const roomsRoute = managerRoomRoutes.index();
const managersRoute = '/admin/managers';
const receptionistsRoute = '/admin/receptionists';

const toneMap = {
    emerald: 'text-emerald-600 dark:text-emerald-400',
    amber: 'text-amber-600 dark:text-amber-400',
    blue: 'text-primary',
} as const;

const recentActivities = computed(() => props.dashboardData.recent_activity);

const pendingClientsCount = computed(() => props.dashboardData.pending_actions.pending_clients.count);
const totalClientsCount = computed(() => props.dashboardData.system_overview.total_clients);

const availableRoomsCount = computed(() => props.dashboardData.quick_stats.available_rooms);
const totalRoomsCount = computed(() => props.dashboardData.quick_stats.total_rooms);

const occupiedRoomsCount = computed(() => {
    return Math.max(totalRoomsCount.value - availableRoomsCount.value, 0);
});

const pendingClientsPercent = computed(() => {
    const total = totalClientsCount.value;

    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((pendingClientsCount.value / total) * 100));
});

const roomsAvailabilityPercent = computed(() => {
    const total = totalRoomsCount.value;

    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((availableRoomsCount.value / total) * 100));
});

const roomsOccupancyPercent = computed(() => {
    const total = totalRoomsCount.value;

    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((occupiedRoomsCount.value / total) * 100));
});

const highlights = computed<HighlightCard[]>(() => {
    return [
        {
            label: 'Pending approvals',
            value: String(pendingClientsCount.value),
            note: totalClientsCount.value > 0 ? `${pendingClientsPercent.value}% of clients` : 'No clients yet',
            toneClass: toneMap.amber,
            ringClass: 'ring-amber-500/15',
            icon: Users,
            barPercent: pendingClientsPercent.value,
            barClass: 'bg-amber-500',
            panelClass: 'from-amber-500/18 via-background/72 to-background/55',
            chipClass: 'border-amber-500/25 bg-amber-500/12',
        },
        {
            label: 'Room availability',
            value:
                totalRoomsCount.value > 0
                    ? `${availableRoomsCount.value}/${totalRoomsCount.value}`
                    : String(availableRoomsCount.value),
            note: totalRoomsCount.value > 0 ? `${roomsAvailabilityPercent.value}% available` : 'Rooms not configured',
            toneClass: toneMap.emerald,
            ringClass: 'ring-emerald-500/15',
            icon: DoorOpen,
            barPercent: roomsAvailabilityPercent.value,
            barClass: 'bg-emerald-500',
            panelClass: 'from-emerald-500/16 via-background/72 to-background/55',
            chipClass: 'border-emerald-500/25 bg-emerald-500/10',
        },
        {
            label: 'Rooms occupied',
            value:
                totalRoomsCount.value > 0
                    ? `${occupiedRoomsCount.value}/${totalRoomsCount.value}`
                    : String(occupiedRoomsCount.value),
            note: totalRoomsCount.value > 0 ? `${roomsOccupancyPercent.value}% occupied` : 'Waiting for inventory',
            toneClass: toneMap.blue,
            ringClass: 'ring-primary/15',
            icon: Activity,
            barPercent: roomsOccupancyPercent.value,
            barClass: 'bg-primary',
            panelClass: 'from-primary/14 via-background/72 to-background/55',
            chipClass: 'border-primary/25 bg-primary/10',
        },
    ];
});

const focusPanelItems = computed<FocusPanelItem[]>(() => {
    return [
        {
            label: 'Approvals waiting',
            value: String(pendingClientsCount.value),
            hint: pendingClientsCount.value > 0 ? 'Check now' : 'No pending items',
            toneClass: toneMap.amber,
            dotClass: 'bg-amber-500',
        },
        {
            label: 'Rooms still open',
            value: String(availableRoomsCount.value),
            hint: totalRoomsCount.value > 0 ? `${roomsAvailabilityPercent.value}% available` : 'No rooms yet',
            toneClass: toneMap.emerald,
            dotClass: 'bg-emerald-500',
        },
        {
            label: 'Live stays now',
            value: String(props.dashboardData.quick_stats.active_reservations),
            hint: 'Current stays',
            toneClass: toneMap.blue,
            dotClass: 'bg-primary',
        },
    ];
});

const systemTiles = computed<SummaryTile[]>(() => {
    return [
        {
            label: 'Total Clients',
            value: props.dashboardData.system_overview.total_clients,
            toneClass: toneMap.blue,
            panelClass: 'from-primary/12 via-background/55 to-background/45',
        },
        {
            label: 'Total Managers',
            value: props.dashboardData.system_overview.total_managers,
            toneClass: toneMap.amber,
            panelClass: 'from-amber-500/12 via-background/55 to-background/45',
        },
        {
            label: 'Total Receptionists',
            value: props.dashboardData.system_overview.total_receptionists,
            toneClass: toneMap.emerald,
            panelClass: 'from-emerald-500/12 via-background/55 to-background/45',
        },
    ];
});

const quickStatTiles = computed<QuickStatTile[]>(() => {
    return [
        {
            label: 'Active Reservations',
            value: String(props.dashboardData.quick_stats.active_reservations),
            toneClass: toneMap.blue,
            panelClass: 'from-primary/12 via-background/55 to-background/45',
            progress: Math.min(props.dashboardData.quick_stats.active_reservations * 10, 100),
            progressClass: 'bg-primary',
            helper: 'Live load',
        },
        {
            label: 'Available Rooms',
            value: `${props.dashboardData.quick_stats.available_rooms}/${props.dashboardData.quick_stats.total_rooms}`,
            toneClass: toneMap.emerald,
            panelClass: 'from-emerald-500/12 via-background/55 to-background/45',
            progress: roomsAvailabilityPercent.value,
            progressClass: 'bg-emerald-500',
            helper: `${roomsAvailabilityPercent.value}% available`,
        },
        {
            label: 'Total Floors',
            value: String(props.dashboardData.quick_stats.total_floors),
            toneClass: toneMap.amber,
            panelClass: 'from-amber-500/12 via-background/55 to-background/45',
            progress: Math.min(props.dashboardData.quick_stats.total_floors * 10, 100),
            progressClass: 'bg-amber-500',
            helper: 'Hotel layout',
        },
    ];
});

const navigationTiles = computed<NavigationTile[]>(() => {
    return [
        {
            label: 'Pending Clients',
            description: 'Open the pending client list.',
            href: pendingClientsRoute,
            icon: Users,
            toneClass: toneMap.amber,
            panelClass: 'from-amber-500/14 via-background/70 to-background/50',
        },
        {
            label: 'Approved Clients',
            description: 'Open the approved client list.',
            href: approvedClientsRoute,
            icon: UserCheck,
            toneClass: toneMap.blue,
            panelClass: 'from-primary/12 via-background/70 to-background/50',
        },
        {
            label: 'Floors',
            description: 'Open floor management.',
            href: floorsRoute,
            icon: Layers3,
            toneClass: toneMap.amber,
            panelClass: 'from-amber-500/10 via-background/70 to-background/50',
        },
        {
            label: 'Rooms',
            description: 'Open room management.',
            href: roomsRoute,
            icon: DoorOpen,
            toneClass: toneMap.emerald,
            panelClass: 'from-emerald-500/12 via-background/70 to-background/50',
        },
        {
            label: 'Manage Managers',
            description: 'Open manager accounts.',
            href: managersRoute,
            icon: ShieldCheck,
            toneClass: toneMap.blue,
            panelClass: 'from-primary/10 via-background/70 to-background/50',
        },
        {
            label: 'Manage Receptionists',
            description: 'Open receptionist accounts.',
            href: receptionistsRoute,
            icon: Users,
            toneClass: toneMap.emerald,
            panelClass: 'from-emerald-500/10 via-background/70 to-background/50',
        },
    ];
});

const actionCenterItems = computed<ActionCenterItem[]>(() => {
    return [
        {
            label: 'Pending Clients',
            value: String(pendingClientsCount.value),
            note: pendingClientsCount.value > 0 ? 'Waiting for review' : 'No pending clients',
            href: props.dashboardData.pending_actions.pending_clients.href,
            cta: 'Review clients',
            icon: Users,
            toneClass: toneMap.amber,
            chipClass: 'border-amber-500/20 bg-amber-500/10',
        },
        {
            label: 'Approved Clients',
            value: String(props.dashboardData.system_overview.total_clients - pendingClientsCount.value),
            note: 'Clients already approved',
            href: approvedClientsRoute,
            cta: 'Open clients',
            icon: UserCheck,
            toneClass: toneMap.blue,
            chipClass: 'border-primary/20 bg-primary/10',
        },
        {
            label: 'Rooms',
            value: `${availableRoomsCount.value}/${totalRoomsCount.value}`,
            note: roomsAvailabilityPercent.value > 0 ? `${roomsAvailabilityPercent.value}% available now` : 'Room status needs review',
            href: roomsRoute,
            cta: 'Open rooms',
            icon: DoorOpen,
            toneClass: toneMap.emerald,
            chipClass: 'border-emerald-500/20 bg-emerald-500/10',
        },
    ];
});

const operationsChartItems = computed<OperationsChartItem[]>(() => {
    return [
        {
            label: 'Pending approvals',
            value: pendingClientsPercent.value,
            count: pendingClientsCount.value,
            toneClass: toneMap.amber,
            barClass: 'from-amber-400 to-amber-600',
            chipClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
            helper: 'pending clients',
        },
        {
            label: 'Occupied rooms',
            value: roomsOccupancyPercent.value,
            count: occupiedRoomsCount.value,
            toneClass: toneMap.blue,
            barClass: 'from-primary/70 to-primary',
            chipClass: 'bg-primary/10 text-primary',
            helper: 'occupied rooms',
        },
        {
            label: 'Available rooms',
            value: roomsAvailabilityPercent.value,
            count: availableRoomsCount.value,
            toneClass: toneMap.emerald,
            barClass: 'from-emerald-400 to-emerald-600',
            chipClass: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
            helper: 'available rooms',
        },
    ];
});

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 rounded-[1.75rem] bg-[linear-gradient(180deg,hsl(var(--background)),hsl(var(--background))_58%,hsl(var(--muted)/0.35))] p-6 lg:p-10">
            <Card class="relative overflow-hidden border-sidebar-border/50 bg-linear-to-br from-card via-card to-muted/30 shadow-[0_24px_80px_-48px_hsl(var(--foreground)/0.35)] dark:border-sidebar-border">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,hsl(var(--primary)/0.18),transparent_28%),radial-gradient(circle_at_80%_20%,rgba(16,185,129,0.16),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(245,158,11,0.14),transparent_24%)]" />
                <div class="absolute -left-20 top-10 size-52 rounded-full bg-primary/10 blur-3xl" />
                <div class="absolute right-8 top-6 size-40 rounded-full bg-emerald-500/10 blur-3xl" />
                <CardHeader class="relative z-10 grid gap-6 border-b border-sidebar-border/70 pb-8 dark:border-sidebar-border xl:grid-cols-[minmax(0,1.6fr)_320px]">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="outline" class="border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm">
                                <ShieldCheck class="mr-1 size-3" />
                                Admin Workspace
                            </Badge>
                            <Badge variant="secondary" class="bg-primary/10 text-primary shadow-sm">
                                Hotel Operations
                            </Badge>
                        </div>
                        <div class="space-y-3">
                            <CardTitle class="max-w-3xl text-3xl font-bold tracking-tight text-foreground/90 lg:text-4xl">
                                Welcome back, {{ currentUserName }}
                            </CardTitle>
                            <CardDescription class="max-w-2xl text-sm leading-6 lg:text-base">
                                Manage client approvals, rooms, and today’s priorities.
                            </CardDescription>
                        </div>

                        <div class="grid gap-3 pt-1 sm:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="item in highlights"
                                :key="item.label"
                                class="rounded-2xl border border-border/60 bg-linear-to-br px-4 py-4 shadow-sm ring-1 backdrop-blur-sm transition-transform duration-300 hover:-translate-y-0.5 dark:border-sidebar-border"
                                :class="[item.ringClass, item.panelClass]"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">
                                            {{ item.label }}
                                        </p>
                                        <p :class="['mt-1 text-xl font-semibold', item.toneClass]">
                                            {{ item.value }}
                                        </p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ item.note }}
                                        </p>
                                    </div>
                                    <div class="rounded-2xl border p-2 text-muted-foreground shadow-sm" :class="item.chipClass">
                                        <component :is="item.icon" :class="['size-4', item.toneClass]" />
                                    </div>
                                </div>

                                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted/50">
                                    <div
                                        class="h-full rounded-full transition-[width] duration-500"
                                        :class="item.barClass"
                                        :style="{ width: `${item.barPercent}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-border/60 bg-background/70 p-5 shadow-sm backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">Admin Summary</p>
                                <h3 class="mt-2 text-lg font-semibold text-foreground">Today</h3>
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                <span class="size-2 rounded-full bg-primary" />
                                Live
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div
                                v-for="item in focusPanelItems"
                                :key="item.label"
                                class="rounded-2xl border border-border/60 bg-card/70 px-4 py-3"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="size-2.5 rounded-full shadow-sm" :class="item.dotClass" />
                                        <div>
                                            <p class="text-sm font-medium text-foreground">{{ item.label }}</p>
                                            <p class="text-xs text-muted-foreground">{{ item.hint }}</p>
                                        </div>
                                    </div>
                                    <p :class="['text-lg font-semibold', item.toneClass]">{{ item.value }}</p>
                                </div>
                            </div>
                        </div>

                        <Link
                            :href="pendingClientsCount > 0 ? pendingClientsRoute : roomsRoute"
                            class="mt-5 block rounded-2xl border border-primary/15 bg-primary/6 p-4 transition-colors hover:bg-primary/10"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Next Step</p>
                                    <p class="mt-2 text-sm font-medium text-foreground">
                                        {{
                                            pendingClientsCount > 0
                                                ? 'Review pending clients.'
                                                : 'Check rooms and availability.'
                                        }}
                                    </p>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        {{
                                            pendingClientsCount > 0
                                                ? 'Open the approval queue and finish pending reviews.'
                                                : 'Client approvals are clear. Review room status next.'
                                        }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-background/80 p-2 text-primary shadow-sm">
                                    <ArrowRight class="size-4" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between rounded-2xl border border-border/60 bg-background/70 px-3 py-2 text-sm">
                                <span class="text-muted-foreground">
                                    {{ pendingClientsCount > 0 ? 'Open Pending Clients' : 'Open Rooms' }}
                                </span>
                                <ArrowRight class="size-4 text-primary" />
                            </div>
                        </Link>
                    </div>
                </CardHeader>

                <CardContent class="relative z-10 pb-8 pt-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.18em] text-muted-foreground">Quick Access</p>
                                <p class="mt-1 text-sm text-foreground">Open the main admin pages.</p>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Link
                                v-for="item in navigationTiles"
                                :key="item.label"
                                :href="item.href"
                                class="group rounded-3xl border border-border/60 bg-linear-to-br p-4 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md"
                                :class="item.panelClass"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-foreground">{{ item.label }}</p>
                                        <p class="mt-1 text-sm text-muted-foreground">{{ item.description }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-border/60 bg-background/80 p-2 shadow-sm">
                                        <component :is="item.icon" :class="['size-4', item.toneClass]" />
                                    </div>
                                </div>
                                <div class="mt-5 flex items-center justify-between text-xs">
                                    <span :class="['font-medium', item.toneClass]">Open section</span>
                                    <ArrowRight class="size-4 text-muted-foreground transition-transform duration-300 group-hover:translate-x-0.5" />
                                </div>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 xl:grid-cols-3">
                <Card class="border-sidebar-border/70 xl:col-span-2 dark:border-sidebar-border">
                    <CardHeader>
                        <CardTitle>Pending Actions</CardTitle>
                        <CardDescription>Items that need admin action.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Link
                            v-for="item in actionCenterItems"
                            :key="item.label"
                            :href="item.href"
                            class="group rounded-3xl border border-sidebar-border/70 bg-linear-to-br from-background via-background to-muted/20 p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-sidebar-border"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                                        <component :is="item.icon" :class="['size-4', item.toneClass]" />
                                        {{ item.label }}
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ item.note }}</p>
                                </div>
                                <div class="rounded-2xl border p-2 shadow-sm" :class="item.chipClass">
                                    <ArrowRight :class="['size-4', item.toneClass]" />
                                </div>
                            </div>
                            <p :class="['mt-6 text-3xl font-semibold tracking-tight', item.toneClass]">{{ item.value }}</p>
                            <div class="mt-5 flex items-center justify-between text-xs">
                                <span :class="['font-medium', item.toneClass]">{{ item.cta }}</span>
                                <ArrowRight class="size-4 text-muted-foreground transition-transform duration-300 group-hover:translate-x-0.5" />
                            </div>
                        </Link>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader>
                        <CardTitle>System Overview</CardTitle>
                        <CardDescription>System totals.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        <div
                            v-for="item in systemTiles"
                            :key="item.label"
                            class="rounded-2xl border border-sidebar-border/70 bg-linear-to-br p-4 shadow-sm dark:border-sidebar-border"
                            :class="item.panelClass"
                        >
                            <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">{{ item.label }}</p>
                            <p :class="['mt-2 text-2xl font-semibold', item.toneClass]">{{ item.value }}</p>
                        </div>
                    </CardContent>
                </Card>

            </div>

            <div class="grid gap-6 xl:grid-cols-7">
                <Card class="border-sidebar-border/70 xl:col-span-3 dark:border-sidebar-border">
                    <CardHeader>
                        <CardTitle>Quick Stats</CardTitle>
                        <CardDescription>Today&apos;s system snapshot.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        <div
                            v-for="item in quickStatTiles"
                            :key="item.label"
                            class="rounded-2xl border border-sidebar-border/70 bg-linear-to-br p-4 shadow-sm dark:border-sidebar-border"
                            :class="item.panelClass"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">{{ item.label }}</p>
                                    <p :class="['mt-2 text-2xl font-semibold', item.toneClass]">{{ item.value }}</p>
                                </div>
                                <div class="rounded-full border border-border/60 bg-background/70 px-2 py-1 text-[11px] text-muted-foreground">
                                    {{ item.helper }}
                                </div>
                            </div>
                            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted/60">
                                <div class="h-full rounded-full" :class="item.progressClass" :style="{ width: `${item.progress}%` }" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="overflow-hidden border-sidebar-border/50 bg-card/60 shadow-sm backdrop-blur-sm transition-all duration-300 hover:shadow-md xl:col-span-4">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-lg">Room Status</CardTitle>
                                <CardDescription>Client approvals, occupied rooms, and available rooms.</CardDescription>
                            </div>
                            <Activity class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="relative overflow-hidden rounded-3xl border border-border/60 bg-linear-to-br from-primary/8 via-background to-emerald-500/6 p-6">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,hsl(var(--primary)/0.12),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%)]" />
                            <div class="relative z-10 grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(260px,0.65fr)]">
                                <div class="rounded-3xl border border-border/60 bg-background/80 p-5 shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">Status Chart</p>
                                            <p class="mt-1 text-sm font-medium text-foreground">Check approvals and room load.</p>
                                        </div>
                                        <TrendingUp class="size-4 text-primary" />
                                    </div>

                                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                                        <div
                                            v-for="item in operationsChartItems"
                                            :key="item.label"
                                            class="flex min-h-52 flex-col justify-end gap-3 md:min-h-60"
                                        >
                                            <div class="flex flex-1 items-end justify-center rounded-3xl border border-border/60 bg-[linear-gradient(180deg,hsl(var(--muted)/0.18),transparent)] px-3 pb-3 pt-5">
                                                <div class="relative flex h-full w-full items-end justify-center">
                                                    <div class="absolute inset-x-2 inset-y-0 rounded-[1.25rem] border border-dashed border-border/50" />
                                                    <div class="absolute inset-x-4 top-1/4 border-t border-dashed border-border/40" />
                                                    <div class="absolute inset-x-4 top-2/4 border-t border-dashed border-border/40" />
                                                    <div class="absolute inset-x-4 top-3/4 border-t border-dashed border-border/40" />
                                                    <div
                                                        class="relative z-10 flex w-14 items-start justify-center rounded-t-[1.25rem] bg-linear-to-t shadow-[0_10px_30px_-12px_rgba(0,0,0,0.35)] transition-all duration-500 md:w-16"
                                                        :class="item.barClass"
                                                        :style="{ height: `${Math.max(item.value, 10)}%` }"
                                                    >
                                                        <span class="mt-3 rounded-full bg-background/85 px-2 py-1 text-xs font-semibold text-foreground shadow-sm">
                                                            {{ item.value }}%
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="rounded-2xl border border-border/60 bg-background/70 p-3 shadow-sm">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-medium text-foreground">{{ item.label }}</p>
                                                        <p class="mt-1 text-xs text-muted-foreground">{{ item.helper }}</p>
                                                    </div>
                                                    <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="item.chipClass">
                                                        {{ item.count }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-1">
                                    <div class="rounded-3xl border border-amber-500/20 bg-amber-500/8 p-4 shadow-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">Priority</p>
                                                <p class="mt-2 text-base font-semibold text-foreground">
                                                    {{
                                                        pendingClientsCount > 0
                                                            ? 'Pending client approvals need action.'
                                                            : 'Client approval queue is clear.'
                                                    }}
                                                </p>
                                            </div>
                                            <Users class="size-4 text-amber-600 dark:text-amber-400" />
                                        </div>
                                        <p class="mt-2 text-sm text-muted-foreground">
                                            {{
                                                pendingClientsCount > 0
                                                    ? 'Review pending clients first.'
                                                    : 'Review rooms or staff next.'
                                            }}
                                        </p>
                                    </div>

                                    <div class="rounded-3xl border border-border/60 bg-background/80 p-4 shadow-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">Staff</p>
                                                <p class="mt-2 text-2xl font-semibold text-primary">
                                                    {{ props.dashboardData.system_overview.total_managers + props.dashboardData.system_overview.total_receptionists }}
                                                </p>
                                                <p class="mt-1 text-sm text-muted-foreground">Manager and receptionist accounts.</p>
                                            </div>
                                            <ShieldCheck class="size-4 text-primary" />
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-border/60 bg-background/80 p-4 shadow-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">Floors</p>
                                                <p class="mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                                                    {{ props.dashboardData.quick_stats.total_floors }}
                                                </p>
                                                <p class="mt-1 text-sm text-muted-foreground">Configured hotel floors.</p>
                                            </div>
                                            <Layers3 class="size-4 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6">
                <Card class="flex flex-col border-sidebar-border/50 bg-card/60 shadow-sm backdrop-blur-sm transition-all duration-300 hover:shadow-md">
                    <CardHeader>
                        <CardTitle class="text-lg">Recent Activity</CardTitle>
                        <CardDescription>Recent approvals and reservations.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6" v-if="recentActivities.length > 0">
                            <div
                                v-for="(activity, index) in recentActivities"
                                :key="activity.id"
                                class="group relative flex items-start rounded-2xl px-2 py-1 transition-colors hover:bg-muted/20"
                            >
                                <div
                                    v-if="index !== recentActivities.length - 1"
                                    class="absolute -bottom-6 left-4.75 top-10 w-0.5 bg-border/50 transition-colors group-hover:bg-primary/25"
                                />
                                <div class="relative z-10 mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full border border-border/50 bg-background shadow-sm transition-colors group-hover:border-primary/50">
                                    <Activity
                                        v-if="activity.amount === null"
                                        class="size-4 text-muted-foreground transition-colors group-hover:text-primary"
                                    />
                                    <CreditCard
                                        v-else
                                        class="size-4 text-muted-foreground transition-colors group-hover:text-primary"
                                    />
                                </div>

                                <div class="z-10 ml-4 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <p class="text-sm font-semibold leading-none">{{ activity.user }}</p>
                                        <div class="rounded-full bg-muted/60 px-2 py-0.5 text-[11px] text-muted-foreground">{{ activity.time }}</div>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                                        <p class="line-clamp-1 text-sm text-muted-foreground/90">
                                            {{ activity.action }}
                                        </p>
                                        <span
                                            v-if="activity.amount"
                                            :class="[
                                                'ml-2 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold',
                                                activity.positive
                                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                                            ]"
                                        >
                                            {{ activity.amount }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-2xl border border-dashed border-sidebar-border/70 bg-background/70 p-6 text-sm text-muted-foreground dark:border-sidebar-border">No activity yet.</div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
