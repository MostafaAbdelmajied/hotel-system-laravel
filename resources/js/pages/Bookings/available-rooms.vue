<script lang="ts" setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import bookings from '@/routes/bookings';
import reservations from '@/routes/reservations';
import type { BreadcrumbItem, PaginationLink } from '@/types';

type AvailableRoom = {
    id: number;
    number: string;
    capacity: number;
    price_cents: number;
    price_in_dollars: string;
    display_price: string;
    floor_name: string | null;
};

type PaginatedRooms = {
    data: AvailableRoom[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    filters: {
        check_in: string;
        check_out: string;
    };
    rooms: PaginatedRooms;
};

const props = defineProps<Props>();
const page = usePage<{ errors?: Record<string, string> }>();

const filters = reactive({
    check_in: props.filters.check_in,
    check_out: props.filters.check_out,
});
const todayDate = new Date().toISOString().split('T')[0];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Available Rooms',
        href: '/bookings/available-rooms',
    },
];

const totalRooms = computed(() => props.rooms.total);

function searchAvailableRooms(): void {
    router.get(
        bookings.availableRooms.url(),
        {
            check_in: filters.check_in,
            check_out: filters.check_out,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['rooms', 'filters', 'errors'],
        },
    );
}

function visitPage(url: string): void {
    router.get(
        url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['rooms', 'filters', 'errors'],
        },
    );
}

function reserveRoomUrl(roomId: number): string {
    return reservations.rooms.show.url(
        { room: roomId },
        {
            query: {
                check_in: filters.check_in,
                check_out: filters.check_out,
            },
        },
    );
}
</script>

<template>
    <Head title="Available Rooms" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <section
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex flex-col gap-4">
                    <div>
                        <h1 class="text-lg font-semibold">Find Available Rooms</h1>
                        <p class="text-sm text-muted-foreground">
                            Pick a check-in and check-out date to list bookable rooms.
                        </p>
                    </div>

                    <form
                        class="grid gap-4 md:grid-cols-3 md:items-end"
                        @submit.prevent="searchAvailableRooms"
                    >
                        <div class="grid gap-2">
                            <Label for="check_in">Check In</Label>
                            <Input
                                id="check_in"
                                v-model="filters.check_in"
                                :min="todayDate"
                                required
                                type="date"
                            />
                            <InputError :message="page.props.errors?.check_in" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="check_out">Check Out</Label>
                            <Input
                                id="check_out"
                                v-model="filters.check_out"
                                required
                                type="date"
                            />
                            <InputError :message="page.props.errors?.check_out" />
                        </div>

                        <Button type="button" @click="searchAvailableRooms">Search Rooms</Button>
                    </form>
                </div>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold">Results</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ totalRooms }} room{{ totalRooms === 1 ? '' : 's' }} available
                    </p>
                </div>

                <div
                    v-if="rooms.data.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center"
                >
                    <p class="text-sm text-muted-foreground">
                        No rooms are available for the selected date range.
                    </p>
                </div>

                <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="room in rooms.data"
                        :key="room.id"
                        class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <h3 class="text-base font-semibold">Room {{ room.number }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Floor: {{ room.floor_name ?? 'N/A' }}
                        </p>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Capacity</dt>
                                <dd class="font-medium">{{ room.capacity }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Price</dt>
                                <dd class="font-medium">{{ room.display_price }}</dd>
                            </div>
                        </dl>

                        <Link
                            :href="reserveRoomUrl(room.id)"
                            class="mt-4 inline-flex rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                        >
                            Reserve
                        </Link>
                    </article>
                </div>

                <div
                    v-if="rooms.links.length > 3"
                    class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 pt-4 dark:border-sidebar-border"
                >
                    <p class="text-xs text-muted-foreground">
                        Showing {{ rooms.from ?? 0 }} to {{ rooms.to ?? 0 }} of {{ rooms.total }} rooms
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <template
                            v-for="(paginationLink, index) in rooms.links"
                            :key="`${index}-${paginationLink.label}`"
                        >
                            <span
                                v-if="!paginationLink.url"
                                class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground"
                                v-html="paginationLink.label"
                            />
                            <button
                                v-else
                                :class="
                                    paginationLink.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted'
                                "
                                class="rounded-md border px-3 py-1.5 text-xs"
                                type="button"
                                @click="visitPage(paginationLink.url)"
                                v-html="paginationLink.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
