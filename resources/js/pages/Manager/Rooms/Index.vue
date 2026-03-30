<script lang="ts" setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { computed, onBeforeUnmount, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Auth, BreadcrumbItem, Floor, PaginationLink } from '@/types';

type RoomSortKey = 'number' | 'capacity' | 'price' | 'floor' | 'creator';

type RoomFloor = {
    id: number | null;
    name: string | null;
    number: string | null;
};

type Room = {
    id: number;
    number: string;
    capacity: number;
    price: number;
    price_in_dollars: string;
    floor: RoomFloor;
    creator: {
        id: number;
        name: string;
    } | null;
    created_by: number | null;
};

type PaginatedRooms = {
    data: Room[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

type Props = {
    rooms: PaginatedRooms;
    floors: Floor[];
    filters: {
        search?: string;
        floor_id?: number | null;
        sort?: RoomSortKey;
        direction?: 'asc' | 'desc';
        per_page?: number;
    };
};

type RoomFormData = {
    floor_id: string;
    number: string;
    capacity: string;
    price: string;
};

type PageProps = {
    auth: Auth;
    flash?: {
        success?: string;
        error?: string;
    };
};

const props = defineProps<Props>();

const page = usePage<PageProps>();
const showModal = ref(false);
const showConfirmDeleteModal = ref(false);
const editingRoom = ref<Room | null>(null);
const search = ref(props.filters.search ?? '');
const selectedFloor = ref(
    props.filters.floor_id === null || props.filters.floor_id === undefined
        ? ''
        : String(props.filters.floor_id),
);
const perPage = ref(String(props.filters.per_page ?? props.rooms.per_page ?? 10));
const sorting = ref<SortingState>([
    {
        id: props.filters.sort ?? 'number',
        desc: props.filters.direction === 'desc',
    },
]);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Rooms',
        href: '/manager/rooms',
    },
];

const auth = computed(() => page.props.auth);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const currentUserId = computed(() => auth.value.user.id);
const isAdmin = computed(() => auth.value.isAdmin ?? false);

const form = useForm<RoomFormData>({
    floor_id: '',
    number: '',
    capacity: '',
    price: '',
});

let searchTimer: ReturnType<typeof setTimeout> | undefined;

const columns = computed<ColumnDef<Room>[]>(() => {
    const baseColumns: ColumnDef<Room>[] = [
        {
            accessorKey: 'number',
            id: 'number',
            enableSorting: true,
            header: 'Room #',
            cell: ({ row }) => row.original.number,
        },
        {
            accessorKey: 'capacity',
            id: 'capacity',
            enableSorting: true,
            header: 'Capacity',
            cell: ({ row }) => row.original.capacity,
        },
        {
            accessorKey: 'price_in_dollars',
            id: 'price',
            enableSorting: true,
            header: 'Price ($)',
            cell: ({ row }) => row.original.price_in_dollars,
        },
        {
            accessorFn: (room) => room.floor.name ?? 'N/A',
            id: 'floor',
            enableSorting: true,
            header: 'Floor',
            cell: ({ row }) => row.original.floor.name ?? 'N/A',
        },
    ];

    if (isAdmin.value) {
        baseColumns.push({
            accessorFn: (room) => room.creator?.name ?? 'System',
            id: 'creator',
            enableSorting: true,
            header: 'Created By',
            cell: ({ row }) => row.original.creator?.name ?? 'System',
        });
    }

    return baseColumns;
});

const table = useVueTable({
    get data() {
        return props.rooms.data;
    },
    get columns() {
        return columns.value;
    },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    get pageCount() {
        return props.rooms.last_page;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
    },
});

function resetForm(): void {
    form.floor_id = '';
    form.number = '';
    form.capacity = '';
    form.price = '';
    form.clearErrors();
}

function openCreate(): void {
    editingRoom.value = null;
    resetForm();
    showModal.value = true;
}

function openEdit(room: Room): void {
    editingRoom.value = room;
    form.floor_id = room.floor.id === null ? '' : String(room.floor.id);
    form.number = room.number;
    form.capacity = String(room.capacity);
    form.price = room.price_in_dollars;
    form.clearErrors();
    showModal.value = true;
}

function closeModal(): void {
    showModal.value = false;
    editingRoom.value = null;
    form.clearErrors();
}

function openConfirmDeleteModal(room: Room): void {
    editingRoom.value = room;
    showConfirmDeleteModal.value = true;
}

function closeConfirmDeleteModal(): void {
    showConfirmDeleteModal.value = false;
    editingRoom.value = null;
}

function submitForm(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            resetForm();
        },
    };

    if (editingRoom.value !== null) {
        form.put(`/manager/rooms/${editingRoom.value.id}`, options);

        return;
    }

    form.post('/manager/rooms', options);
}

function deleteRoom(): void {
    if (editingRoom.value === null) {
        return;
    }

    router.delete(`/manager/rooms/${editingRoom.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeConfirmDeleteModal(),
    });
}

function canManage(room: Room): boolean {
    return isAdmin.value || room.created_by === currentUserId.value;
}

function normalizePaginationLabel(label: string): string {
    return label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next')
        .replace(/&laquo;|&raquo;/g, '')
        .trim();
}

function buildRoomQuery(pageNumber = 1): Record<string, string | number | undefined> {
    const currentSort = sorting.value[0];

    return {
        search: search.value || undefined,
        floor_id: selectedFloor.value || undefined,
        sort: currentSort?.id,
        direction: currentSort?.desc ? 'desc' : 'asc',
        per_page: Number(perPage.value),
        page: pageNumber,
    };
}

function fetchRooms(pageNumber = 1): void {
    router.get('/manager/rooms', buildRoomQuery(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['rooms', 'filters'],
    });
}

function queueSearch(): void {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        fetchRooms(1);
    }, 300);
}

function applyFilters(): void {
    fetchRooms(1);
}

function toggleSort(columnId: string): void {
    const current = sorting.value[0];

    if (current?.id === columnId) {
        sorting.value = [{ id: columnId, desc: !current.desc }];
    } else {
        sorting.value = [{ id: columnId, desc: false }];
    }

    fetchRooms(1);
}

function sortIndicator(columnId: string): string {
    const current = sorting.value[0];

    if (current?.id !== columnId) {
        return '';
    }

    return current.desc ? 'v' : '^';
}

function visitPage(url: string): void {
    const pageNumber = Number(new URL(url, window.location.origin).searchParams.get('page') ?? 1);

    fetchRooms(pageNumber);
}

onBeforeUnmount(() => {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }
});
</script>

<template>
    <Head title="Manage Rooms" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                v-if="flashSuccess"
                class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
            >
                {{ flashSuccess }}
            </div>

            <div
                v-if="flashError"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ flashError }}
            </div>

            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div
                    class="flex flex-col gap-4 border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h1 class="text-lg font-semibold">Manage Rooms</h1>
                            <p class="text-sm text-muted-foreground">
                                Create, update, and remove hotel rooms with server-side filtering and sorting.
                            </p>
                        </div>

                        <Button type="button" @click="openCreate">
                            Add Room
                        </Button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="grid gap-2">
                            <Label for="room-search">Search</Label>
                            <Input
                                id="room-search"
                                v-model="search"
                                placeholder="Room, floor, or creator"
                                @input="queueSearch"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="room-floor-filter">Floor</Label>
                            <select
                                id="room-floor-filter"
                                v-model="selectedFloor"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                @change="applyFilters"
                            >
                                <option value="">All floors</option>
                                <option
                                    v-for="floor in props.floors"
                                    :key="floor.id"
                                    :value="String(floor.id)"
                                >
                                    {{ floor.name }} (#{{ floor.number }})
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="room-per-page">Rows per page</Label>
                            <select
                                id="room-per-page"
                                v-model="perPage"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                @change="applyFilters"
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="rounded-lg border border-dashed border-sidebar-border/70 px-3 py-2 text-xs text-muted-foreground dark:border-sidebar-border">
                            Sort by clicking table headers. Filters are applied server-side through the query builder.
                        </div>
                    </div>
                </div>

                <div v-if="props.rooms.data.length === 0" class="px-4 py-10 text-center">
                    <p class="text-sm text-muted-foreground">No rooms found.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead>
                            <tr
                                v-for="headerGroup in table.getHeaderGroups()"
                                :key="headerGroup.id"
                                class="bg-muted/40 text-left"
                            >
                                <th
                                    v-for="header in headerGroup.headers"
                                    :key="header.id"
                                    class="px-4 py-3 font-medium"
                                >
                                    <button
                                        v-if="header.column.getCanSort()"
                                        type="button"
                                        class="inline-flex items-center gap-2 transition-colors hover:text-foreground"
                                        @click="toggleSort(header.column.id)"
                                    >
                                        <FlexRender
                                            v-if="!header.isPlaceholder"
                                            :props="header.getContext()"
                                            :render="header.column.columnDef.header"
                                        />
                                        <span class="text-[10px] text-muted-foreground">
                                            {{ sortIndicator(header.column.id) }}
                                        </span>
                                    </button>
                                    <FlexRender
                                        v-else-if="!header.isPlaceholder"
                                        :props="header.getContext()"
                                        :render="header.column.columnDef.header"
                                    />
                                </th>
                                <th class="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr v-for="row in table.getRowModel().rows" :key="row.id">
                                <td
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                    class="px-4 py-3"
                                >
                                    <FlexRender
                                        :props="cell.getContext()"
                                        :render="cell.column.columnDef.cell"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div
                                        v-if="canManage(row.original)"
                                        class="flex items-center gap-2"
                                    >
                                        <Button
                                            size="sm"
                                            type="button"
                                            variant="outline"
                                            @click="openEdit(row.original)"
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            type="button"
                                            variant="destructive"
                                            @click="openConfirmDeleteModal(row.original)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                    <span v-else class="text-muted-foreground">N/A</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.rooms.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <p class="text-xs text-muted-foreground">
                        Showing {{ props.rooms.from ?? 0 }} to
                        {{ props.rooms.to ?? 0 }} of {{ props.rooms.total }} rooms
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <template
                            v-for="(paginationLink, index) in props.rooms.links"
                            :key="`${index}-${paginationLink.label}`"
                        >
                            <span
                                v-if="!paginationLink.url"
                                class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground"
                            >
                                {{ normalizePaginationLabel(paginationLink.label) }}
                            </span>
                            <button
                                v-else
                                :class="paginationLink.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                                class="rounded-md border px-3 py-1.5 text-xs"
                                type="button"
                                @click="visitPage(paginationLink.url)"
                            >
                                {{ normalizePaginationLabel(paginationLink.label) }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-md rounded-xl bg-background p-6 shadow-lg">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ editingRoom ? 'Edit Room' : 'Create Room' }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{
                                editingRoom
                                    ? 'Update the selected room details.'
                                    : 'Add a new room to a floor.'
                            }}
                        </p>
                    </div>

                    <Button
                        size="sm"
                        type="button"
                        variant="ghost"
                        @click="closeModal"
                    >
                        Close
                    </Button>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="floor_id">Floor</Label>
                        <select
                            id="floor_id"
                            v-model="form.floor_id"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        >
                            <option value="">Select floor</option>
                            <option
                                v-for="floor in props.floors"
                                :key="floor.id"
                                :value="String(floor.id)"
                            >
                                {{ floor.name }} (#{{ floor.number }})
                            </option>
                        </select>
                        <InputError :message="form.errors.floor_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="number">Room Number</Label>
                        <Input
                            id="number"
                            v-model="form.number"
                            placeholder="e.g. 1001"
                        />
                        <InputError :message="form.errors.number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="capacity">Capacity</Label>
                        <Input
                            id="capacity"
                            v-model="form.capacity"
                            min="1"
                            type="number"
                        />
                        <InputError :message="form.errors.capacity" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="price">Price (USD)</Label>
                        <Input
                            id="price"
                            v-model="form.price"
                            min="0"
                            step="0.01"
                            type="number"
                        />
                        <InputError :message="form.errors.price" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="closeModal">
                            Cancel
                        </Button>
                        <Button :disabled="form.processing" type="submit">
                            {{
                                form.processing
                                    ? 'Saving...'
                                    : editingRoom
                                      ? 'Update Room'
                                      : 'Create Room'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="showConfirmDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-md rounded-xl bg-background p-6 shadow-lg">
                <div class="flex flex-col gap-4">
                    <h2 class="text-lg font-semibold">Delete Room</h2>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete room "{{ editingRoom?.number }}"? This action cannot be undone.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeConfirmDeleteModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="deleteRoom"
                    >
                        Delete Room
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
