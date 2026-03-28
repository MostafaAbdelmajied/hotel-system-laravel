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
import type { Auth, BreadcrumbItem, Floor, PaginationLink, User } from '@/types';

type FloorFormData = {
    name: string;
    number: string;
    managed_by: string;
};

type PaginatedFloors = {
    data: Floor[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    floors: PaginatedFloors;
    managers: User[];
    filters: {
        search?: string;
    };
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
const editingFloor = ref<Floor | null>(null);
const search = ref(props.filters.search ?? '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Floors',
        href: '/manager/floors',
    },
];

const auth = computed(() => page.props.auth);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const currentUserId = computed(() => auth.value.user.id);
const isAdmin = computed(() => auth.value.isAdmin ?? false);

const form = useForm<FloorFormData>({
    name: '',
    number: '',
    managed_by: '',
});

let searchTimer: ReturnType<typeof setTimeout> | undefined;

const columns = computed<ColumnDef<Floor>[]>(() => {
    const baseColumns: ColumnDef<Floor>[] = [
        {
            accessorKey: 'number',
            header: 'Floor #',
            cell: ({ row }) => row.original.number,
        },
        {
            accessorKey: 'name',
            header: 'Name',
            cell: ({ row }) => row.original.name,
        },
        {
            accessorFn: (floor) => floor.manager?.name ?? 'System',
            id: 'manager',
            header: 'Manager',
            cell: ({ row }) => row.original.manager?.name ?? 'System',
        },
    ];

    if (isAdmin.value) {
        baseColumns.push({
            accessorFn: (floor) => floor.creator?.name ?? 'System',
            id: 'creator',
            header: 'Created By',
            cell: ({ row }) => row.original.creator?.name ?? 'System',
        });
    }

    return baseColumns;
});

const table = useVueTable({
    get data() {
        return props.floors.data;
    },
    get columns() {
        return columns.value;
    },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    get pageCount() {
        return props.floors.last_page;
    },
});

function resetForm(): void {
    form.name = '';
    form.number = '';
    form.managed_by = '';
    form.clearErrors();
}

function generateSerials() {
    const randomChars = Math.random()
        .toString(36)
        .substring(2, 5)
        .toUpperCase();
    const randomNumber = Math.floor(100 + Math.random() * 900);
    form.name = `FLR-${randomChars}`;
    form.number = `F-${randomNumber}`;
}

function openCreate(): void {
    editingFloor.value = null;
    resetForm();

    // Auto-select current user if they are a manager
    if (auth.value.isManager) {
        form.managed_by = String(currentUserId.value);
    }

    showModal.value = true;
}

function openEdit(floor: Floor): void {
    editingFloor.value = floor;
    form.name = floor.name;
    form.number = floor.number;
    form.managed_by = floor.manager?.id ? String(floor.manager.id) : '';
    form.clearErrors();
    showModal.value = true;
}

function closeModal(): void {
    showModal.value = false;
    editingFloor.value = null;
    form.clearErrors();
}

function openConfirmDeleteModal(floor: Floor): void {
    editingFloor.value = floor;
    showConfirmDeleteModal.value = true;
}

function closeConfirmDeleteModal(): void {
    showConfirmDeleteModal.value = false;
    editingFloor.value = null;
}

function submitForm(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            resetForm();
        },
    };

    if (editingFloor.value !== null) {
        form.put(`/manager/floors/${editingFloor.value.id}`, options);
    } else {
        form.post('/manager/floors', options);
    }
}

function deleteFloor(): void {
    if (!editingFloor.value) {
        return;
    }

    router.delete(`/manager/floors/${editingFloor.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeConfirmDeleteModal(),
    });
}

function canManage(floor: Floor): boolean {
    return isAdmin.value || floor.created_by === currentUserId.value;
}

function filterFloors(): void {
    router.get(
        '/manager/floors',
        {
            search: search.value || undefined,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['floors', 'filters'],
        },
    );
}

function queueSearch(): void {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        filterFloors();
    }, 300);
}

function visitPage(url: string): void {
    router.get(
        url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['floors', 'filters'],
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
    <Head title="Manage Floors" />

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
                        <h1 class="text-lg font-semibold">Manage Floors</h1>
                        <p class="text-sm text-muted-foreground">
                            Create, update, and remove hotel floors.
                        </p>
                    </div>

                    <div
                        class="flex flex-col gap-3 md:w-auto md:flex-row md:items-end"
                    >
                        <div class="w-full md:w-72">
                            <Label for="floor-search"
                                >Search by name or number</Label
                            >
                            <Input
                                id="floor-search"
                                v-model="search"
                                class="mt-2"
                                placeholder="e.g. Floor 1"
                                @input="queueSearch"
                            />
                        </div>

                        <Button type="button" @click="openCreate">
                            Add Floor
                        </Button>
                    </div>
                </div>

                <div
                    v-if="props.floors.data.length === 0"
                    class="px-4 py-10 text-center"
                >
                    <p class="text-sm text-muted-foreground">
                        No floors found.
                    </p>
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
                                            @click="
                                                openConfirmDeleteModal(
                                                    row.original,
                                                )
                                            "
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
                    v-if="props.floors.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <p class="text-xs text-muted-foreground">
                        Showing {{ props.floors.from ?? 0 }} to
                        {{ props.floors.to ?? 0 }} of
                        {{ props.floors.total }} floors
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <template
                            v-for="(paginationLink, index) in props.floors
                                .links"
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
                            {{ editingFloor ? 'Edit Floor' : 'Create Floor' }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{
                                editingFloor
                                    ? 'Update the selected floor details.'
                                    : 'Add a new floor to the hotel.'
                            }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        @click="generateSerials"
                    >
                        Generate
                    </Button>
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
                    <div class="flex items-end gap-2">
                        <div class="grid flex-1 gap-2">
                            <Label for="name">Floor Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. FLR-ABC"
                            />
                        </div>
                    </div>
                    <InputError :message="form.errors.name" />

                    <div class="grid gap-2">
                        <Label for="number">Floor Number</Label>
                        <Input
                            id="number"
                            v-model="form.number"
                            placeholder="e.g. F-123"
                        />
                        <InputError :message="form.errors.number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="managed_by">Manager</Label>
                        <select
                            id="managed_by"
                            v-model="form.managed_by"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        >
                            <option value="">Select manager</option>
                            <option
                                v-for="manager in props.managers"
                                :key="manager.id"
                                :value="String(manager.id)"
                            >
                                {{ manager.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.managed_by" />
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
                                    : editingFloor
                                      ? 'Update Floor'
                                      : 'Create Floor'
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
                    <h2 class="text-lg font-semibold">Delete Floor</h2>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete floor "{{
                            editingFloor?.name
                        }}"? This action cannot be undone.
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
                        @click="deleteFloor"
                    >
                        Delete Floor
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
