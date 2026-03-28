<script lang="ts" setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { computed, onBeforeUnmount, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Auth, BreadcrumbItem, Floor, PaginationLink } from '@/types';

type Room = {
    id: number;
    number: string;
    capacity: number;
    price: number;
    price_in_dollars: string;
    floor: Floor;
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
};

type Props = {
    rooms: PaginatedRooms;
    floors: Floor[];
    filters: {
        search?: string;
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
const editingRoom = ref<Room | null>(null);
const search = ref(props.filters.search ?? '');

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
            header: 'Room #',
            cell: ({ row }) => row.original.number,
        },
        {
            accessorKey: 'capacity',
            header: 'Capacity',
            cell: ({ row }) => row.original.capacity,
        },
        {
            accessorKey: 'price_in_dollars',
            header: 'Price ($)',
            cell: ({ row }) => row.original.price_in_dollars,
        },
        {
            accessorFn: (room) => room.floor.name ?? 'N/A',
            id: 'floor',
            header: 'Floor',
            cell: ({ row }) => row.original.floor.name ?? 'N/A',
        },
    ];

    if (isAdmin.value) {
        baseColumns.push({
            accessorFn: (room) => room.creator?.name ?? 'System',
            id: 'manager_name',
            header: 'Manager Name',
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
    get pageCount() {
        return props.rooms.last_page;
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

function deleteRoom(room: Room): void {
    if (!window.confirm(`Delete room ${room.number}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/manager/rooms/${room.id}`, {
        preserveScroll: true,
    });
}

function canManage(room: Room): boolean {
    return isAdmin.value || room.created_by === currentUserId.value;
}

function filterRooms(): void {
    router.get(
        '/manager/rooms',
        {
            search: search.value || undefined,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['rooms', 'filters'],
        },
    );
}

function queueSearch(): void {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        filterRooms();
    }, 300);
}

function visitPage(url: string): void {
    router.get(
        url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['rooms', 'filters'],
        },
    );
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

            <div
                class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <div
                    class="flex flex-col gap-4 border-b border-sidebar-border/70 px-4 py-3 md:flex-row md:items-end md:justify-between dark:border-sidebar-border"
                >
                    <div>
                        <h1 class="text-lg font-semibold">Manage Rooms</h1>
                        <p class="text-sm text-muted-foreground">
                            Create, update, and remove hotel rooms.
                        </p>
                    </div>

                    <div
                        class="flex flex-col gap-3 md:w-auto md:flex-row md:items-end"
                    >
                        <div class="w-full md:w-72">
                            <Label for="room-search"
                                >Search by room number</Label
                            >
                            <Input
                                id="room-search"
                                v-model="search"
                                class="mt-2"
                                placeholder="e.g. 1001"
                                @input="queueSearch"
                            />
                        </div>

                        <Button type="button" @click="openCreate">
                            Add Room
                        </Button>
                    </div>
                </div>

                <div
                    v-if="props.rooms.data.length === 0"
                    class="px-4 py-10 text-center"
                >
                    <p class="text-sm text-muted-foreground">No rooms found.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border"
                    >
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
                                    <FlexRender
                                        v-if="!header.isPlaceholder"
                                        :props="header.getContext()"
                                        :render="header.column.columnDef.header"
                                    />
                                </th>
                                <th class="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border"
                        >
                            <tr
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                            >
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
                                            @click="deleteRoom(row.original)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                    <span v-else class="text-muted-foreground"
                                        >N/A</span
                                    >
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
                        {{ props.rooms.to ?? 0 }} of
                        {{ props.rooms.total }} rooms
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <template
                            v-for="(paginationLink, index) in props.rooms.links"
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
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                        >
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
    </AppLayout>
</template>
