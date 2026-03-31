<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BedDouble,
    CalendarDays,
    Clock3,
    CreditCard,
    Sparkles,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import bookings from '@/routes/bookings';
import { dashboard } from '@/routes';
import reservations from '@/routes/reservations';
import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const page = usePage<{ auth: Auth }>();
const isClient = page.props.auth?.canViewReservations === true;

const today = new Date();
const checkInDate = new Date(today);
checkInDate.setDate(checkInDate.getDate() + 1);

const checkOutDate = new Date(today);
checkOutDate.setDate(checkOutDate.getDate() + 3);

const toDateInputValue = (value: Date): string => {
    return value.toISOString().slice(0, 10);
};

const defaultCheckIn = toDateInputValue(checkInDate);
const defaultCheckOut = toDateInputValue(checkOutDate);

const firstName = computed(() => {
    const name = page.props.auth?.user?.name ?? 'Guest';

    return name.split(' ')[0] ?? name;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div v-if="isClient" class="space-y-5">
                <section
                    class="dashboard-fade relative overflow-hidden rounded-3xl border border-border/60 bg-[radial-gradient(circle_at_top_left,hsl(var(--primary)/0.22),transparent_38%),radial-gradient(circle_at_82%_18%,rgba(16,185,129,0.22),transparent_32%),linear-gradient(165deg,hsl(var(--background))_10%,hsl(var(--muted)/0.7)_100%)] p-6 shadow-[0_30px_80px_-45px_hsl(var(--foreground)/0.45)] md:p-8"
                >
                    <div
                        class="pointer-events-none absolute -top-20 -right-24 size-56 rounded-full bg-emerald-400/20 blur-3xl"
                    />
                    <div
                        class="pointer-events-none absolute -bottom-20 left-20 size-56 rounded-full bg-primary/20 blur-3xl"
                    />

                    <div
                        class="relative z-10 grid gap-6 lg:grid-cols-[1.35fr_0.65fr] lg:items-end"
                    >
                        <div class="space-y-4">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-semibold tracking-[0.12em] text-primary"
                            >
                                <Sparkles class="size-3.5" />
                                CLIENT SPACE
                            </div>

                            <div class="space-y-3">
                                <h1
                                    class="font-['Space_Grotesk'] text-3xl leading-tight font-bold tracking-tight text-foreground md:text-4xl"
                                >
                                    Welcome back, {{ firstName }}
                                </h1>
                                <p
                                    class="max-w-2xl font-['DM_Sans'] text-sm leading-6 text-muted-foreground md:text-base"
                                >
                                    Ready for your next stay? Start a
                                    reservation in seconds, lock your room price
                                    at payment time, and keep every booking in
                                    one clean timeline.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-1">
                                <Link
                                    :href="
                                        bookings.availableRooms.url({
                                            query: {
                                                check_in: defaultCheckIn,
                                                check_out: defaultCheckOut,
                                            },
                                        })
                                    "
                                    class="dashboard-rise inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition-transform duration-300 hover:-translate-y-0.5 hover:opacity-95"
                                >
                                    Start New Booking
                                    <ArrowRight class="size-4" />
                                </Link>
                                <Link
                                    :href="reservations.my()"
                                    class="dashboard-rise inline-flex items-center gap-2 rounded-xl border border-border/70 bg-background/75 px-5 py-3 text-sm font-semibold text-foreground transition-transform duration-300 hover:-translate-y-0.5 hover:bg-muted"
                                >
                                    My Reservations
                                    <CalendarDays class="size-4" />
                                </Link>
                            </div>
                        </div>

                        <div
                            class="dashboard-rise rounded-2xl border border-border/60 bg-background/70 p-5 backdrop-blur-sm"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                            >
                                Booking checklist
                            </p>
                            <div
                                class="mt-4 space-y-3 text-sm text-muted-foreground"
                            >
                                <div class="rounded-xl bg-muted/60 px-3 py-2">
                                    1. Start with “Start New Booking”.
                                </div>
                                <div class="rounded-xl bg-muted/60 px-3 py-2">
                                    2. Choose your check-in and check-out dates.
                                </div>
                                <div class="rounded-xl bg-muted/60 px-3 py-2">
                                    3. Pick a room that matches your guest
                                    count.
                                </div>
                                <div class="rounded-xl bg-muted/60 px-3 py-2">
                                    4. Complete payment to confirm your
                                    reservation.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-3">
                    <article
                        class="dashboard-rise rounded-2xl border border-border/65 bg-card/80 p-5 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-foreground">
                                Capacity-first rooms
                            </p>
                            <BedDouble class="size-4 text-primary" />
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Each room selection validates guest count against
                            room capacity before payment begins.
                        </p>
                    </article>

                    <article
                        class="dashboard-rise rounded-2xl border border-border/65 bg-card/80 p-5 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-foreground">
                                Fast booking flow
                            </p>
                            <Clock3
                                class="size-4 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Choose dates, pick a room, and complete checkout.
                            Availability is rechecked before confirmation.
                        </p>
                    </article>

                    <article
                        class="dashboard-rise rounded-2xl border border-border/65 bg-card/80 p-5 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-foreground">
                                Secure payment snapshots
                            </p>
                            <CreditCard
                                class="size-4 text-amber-600 dark:text-amber-400"
                            />
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Your paid booking price is stored as a snapshot so
                            historical reservations stay accurate.
                        </p>
                    </article>
                </section>
            </div>

            <template v-else>
                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div
                        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <PlaceholderPattern />
                    </div>
                    <div
                        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <PlaceholderPattern />
                    </div>
                    <div
                        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <PlaceholderPattern />
                    </div>
                </div>
                <div
                    class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes dashboardFade {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes dashboardRise {
    from {
        opacity: 0;
        transform: translateY(16px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dashboard-fade {
    animation: dashboardFade 420ms ease-out both;
}

.dashboard-rise {
    animation: dashboardRise 560ms ease-out both;
}
</style>
