<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

type ReservationItem = {
    id: number;
    accompany_number: number;
    paid_price_cents: number;
    check_in: string | null;
    check_out: string | null;
    room: {
        number: string | null;
    } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type ReservationsPagination = {
    data: ReservationItem[];
    links: PaginationLink[];
};

const props = defineProps<{
    reservations: ReservationsPagination;
}>();

const breadcrumbs = [
    {
        title: 'My Reservations',
        href: '/reservations/my',
    },
];

const formatPrice = (valueInCents: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(valueInCents / 100);
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return 'N/A';
    }

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
    }).format(new Date(value));
};

const normalizePaginationLabel = (label: string): string => {
    return label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next')
        .replace(/&laquo;|&raquo;/g, '')
        .trim();
};
</script>

<template>
    <Head title="My Reservations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <h1 class="text-lg font-semibold">My Reservations</h1>
                    <p class="text-sm text-muted-foreground">
                        Your confirmed room bookings and paid price snapshots.
                    </p>
                </div>

                <div v-if="props.reservations.data.length === 0" class="px-4 py-10 text-center">
                    <p class="text-sm text-muted-foreground">You do not have any reservations yet.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead>
                            <tr class="bg-muted/40 text-left">
                                <th class="px-4 py-3 font-medium">Accompany Number</th>
                                <th class="px-4 py-3 font-medium">Room Number</th>
                                <th class="px-4 py-3 font-medium">Paid Price</th>
                                <th class="px-4 py-3 font-medium">Check In</th>
                                <th class="px-4 py-3 font-medium">Check Out</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr v-for="reservation in props.reservations.data" :key="reservation.id">
                                <td class="px-4 py-3">{{ reservation.accompany_number }}</td>
                                <td class="px-4 py-3">{{ reservation.room?.number ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ formatPrice(reservation.paid_price_cents) }}</td>
                                <td class="px-4 py-3">{{ formatDate(reservation.check_in) }}</td>
                                <td class="px-4 py-3">{{ formatDate(reservation.check_out) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.reservations.links.length > 3"
                    class="flex flex-wrap items-center gap-2 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <template v-for="(paginationLink, index) in props.reservations.links" :key="`${index}-${paginationLink.label}`">
                        <span
                            v-if="!paginationLink.url"
                            class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground"
                        >
                            {{ normalizePaginationLabel(paginationLink.label) }}
                        </span>
                        <Link
                            v-else
                            :href="paginationLink.url"
                            class="rounded-md border px-3 py-1.5 text-xs"
                            :class="paginationLink.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            preserve-scroll
                        >
                            {{ normalizePaginationLabel(paginationLink.label) }}
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
