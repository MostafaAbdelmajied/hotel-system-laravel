<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Auth, BreadcrumbItem, PaginationLink } from '@/types';

type ManagerRecord = {
    id: number;
    name: string;
    email: string;
    country: string | null;
    gender: string | null;
    avatar: string | null;
    created_at: string;
};

type PaginatedManagers = {
    data: ManagerRecord[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    managers: PaginatedManagers;
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
const managersIndexPath = '/admin/managers';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Managers',
        href: managersIndexPath,
    },
];

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const search = ref(props.filters.search ?? '');
const deletingManager = ref<ManagerRecord | null>(null);

let searchTimer: ReturnType<typeof setTimeout> | undefined;

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
    }).format(new Date(value));
}

function formatGender(value: string | null): string {
    if (value === null || value === '') {
        return 'N/A';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

function normalizePaginationLabel(label: string): string {
    return label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next')
        .replace(/&laquo;|&raquo;/g, '')
        .trim();
}

function fetchManagers(pageNumber = 1): void {
    router.get(managersIndexPath, {
        search: search.value || undefined,
        page: pageNumber,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['managers', 'filters'],
    });
}

function queueSearch(): void {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        fetchManagers(1);
    }, 300);
}

function visitPage(url: string): void {
    const pageNumber = Number(new URL(url, window.location.origin).searchParams.get('page') ?? 1);

    fetchManagers(pageNumber);
}

function deleteManager(): void {
    if (deletingManager.value === null) {
        return;
    }

    router.delete(`/admin/managers/${deletingManager.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deletingManager.value = null;
        },
    });
}

onBeforeUnmount(() => {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }
});
</script>

<template>
    <Head title="Manage Managers" />

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
                <div class="flex flex-col gap-4 border-b border-sidebar-border/70 px-4 py-4 md:flex-row md:items-end md:justify-between dark:border-sidebar-border">
                    <div>
                        <h1 class="text-lg font-semibold">Manage Managers</h1>
                        <p class="text-sm text-muted-foreground">
                            Create, update, and remove manager accounts.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 md:flex-row md:items-end">
                        <div class="w-full md:w-72">
                            <Label for="manager-search">Search by name or email</Label>
                            <Input
                                id="manager-search"
                                v-model="search"
                                class="mt-2"
                                placeholder="e.g. ahmed@example.com"
                                @input="queueSearch"
                            />
                        </div>

                        <Button as-child type="button">
                            <Link :href="`${managersIndexPath}/create`">
                                Create Manager
                            </Link>
                        </Button>
                    </div>
                </div>

                <div v-if="props.managers.data.length === 0" class="px-4 py-10 text-center">
                    <p class="text-sm text-muted-foreground">No managers found.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead>
                            <tr class="bg-muted/40 text-left">
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium">Country</th>
                                <th class="px-4 py-3 font-medium">Gender</th>
                                <th class="px-4 py-3 font-medium">Created At</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr v-for="manager in props.managers.data" :key="manager.id">
                                <td class="px-4 py-3 font-medium text-foreground">{{ manager.name }}</td>
                                <td class="px-4 py-3">{{ manager.email }}</td>
                                <td class="px-4 py-3">{{ manager.country ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ formatGender(manager.gender) }}</td>
                                <td class="px-4 py-3">{{ formatDate(manager.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Button as-child size="sm" type="button" variant="outline">
                                            <Link :href="`${managersIndexPath}/${manager.id}/edit`">
                                                Edit
                                            </Link>
                                        </Button>

                                        <Dialog>
                                            <DialogTrigger as-child>
                                                <Button
                                                    size="sm"
                                                    type="button"
                                                    variant="destructive"
                                                    @click="deletingManager = manager"
                                                >
                                                    Delete
                                                </Button>
                                            </DialogTrigger>

                                            <DialogContent>
                                                <DialogHeader class="space-y-3">
                                                    <DialogTitle>Delete Manager</DialogTitle>
                                                    <DialogDescription>
                                                        Are you sure you want to delete
                                                        {{ deletingManager?.name }}?
                                                        This action cannot be undone.
                                                    </DialogDescription>
                                                </DialogHeader>

                                                <DialogFooter class="gap-2">
                                                    <DialogClose as-child>
                                                        <Button variant="outline">
                                                            Cancel
                                                        </Button>
                                                    </DialogClose>
                                                    <Button type="button" variant="destructive" @click="deleteManager">
                                                        Delete Manager
                                                    </Button>
                                                </DialogFooter>
                                            </DialogContent>
                                        </Dialog>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.managers.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <p class="text-xs text-muted-foreground">
                        Showing {{ props.managers.from ?? 0 }} to
                        {{ props.managers.to ?? 0 }} of
                        {{ props.managers.total }} managers
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <template
                            v-for="(paginationLink, index) in props.managers.links"
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
    </AppLayout>
</template>
