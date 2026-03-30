<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
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
checkOutDate.setDate(checkOutDate.getDate() + 2);

const toDateInputValue = (value: Date): string => {
    return value.toISOString().slice(0, 10);
};

const defaultCheckIn = toDateInputValue(checkInDate);
const defaultCheckOut = toDateInputValue(checkOutDate);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div v-if="isClient" class="grid gap-4 md:grid-cols-2">
                <div
                    class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
                >
                    <h2 class="text-lg font-semibold">Welcome back</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Start a new booking or review your existing reservations.
                        All prices shown in your reservations are locked from the
                        paid snapshot.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Link
                            :href="bookings.availableRooms.url({ query: { check_in: defaultCheckIn, check_out: defaultCheckOut } })"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                        >
                            Book Available Rooms
                        </Link>
                        <Link
                            :href="reservations.my()"
                            class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                        >
                            View My Reservations
                        </Link>
                    </div>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
                >
                    <h3 class="text-base font-semibold">Booking Tips</h3>
                    <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                        <li>Choose check-in and check-out dates first.</li>
                        <li>Pick a room based on capacity and floor.</li>
                        <li>Complete Stripe payment to confirm reservation.</li>
                        <li>Track your bookings from My Reservations.</li>
                    </ul>
                </div>
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
