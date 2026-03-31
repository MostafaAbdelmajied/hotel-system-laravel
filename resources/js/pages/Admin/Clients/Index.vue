<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Filter, RotateCcw, Search } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { approve } from '@/routes/clients';
import type { Auth, BreadcrumbItem, PaginationLink } from '@/types';

type ClientState = 'all' | 'pending' | 'approved' | 'banned';
type SearchField = 'all' | 'name' | 'email' | 'mobile_number' | 'country';

type ClientRecord = {
    id: number;
    name: string;
    email: string;
    mobile_number: string | null;
    country: string | null;
    gender: string | null;
    status: 'pending' | 'approved';
    state: Exclude<ClientState, 'all'>;
    approved_at: string | null;
    approved_by_name: string | null;
    banned_at: string | null;
    created_at: string;
};

type PaginatedClients = {
    data: ClientRecord[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    clients: PaginatedClients;
    filters: {
        search?: string;
        state?: ClientState;
        search_field?: SearchField;
    };
    stats: {
        total: number;
        pending: number;
        approved: number;
        banned: number;
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
const clientsIndexPath = '/admin/clients';
const search = ref(props.filters.search ?? '');
const activeState = ref<ClientState>(props.filters.state ?? 'all');
const searchField = ref<SearchField>(props.filters.search_field ?? 'all');
const approvingClientId = ref<number | null>(null);
const filtersOpen = ref((props.filters.search ?? '').trim() !== '' || (props.filters.state ?? 'all') !== 'all' || (props.filters.search_field ?? 'all') !== 'all');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Clients',
        href: clientsIndexPath,
    },
];

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const exportUrl = computed(() => {
    if (activeState.value === 'pending') {
        return '/clients/pending/export';
    }

    if (activeState.value === 'approved') {
        return '/clients/my-approved/export';
    }

    return null;
});
const exportLabel = computed(() => {
    if (activeState.value === 'pending') {
        return 'Export Pending CSV';
    }

    if (activeState.value === 'approved') {
        return 'Export Approved CSV';
    }

    return 'Export CSV';
});
const hasActiveFilters = computed(() => {
    return search.value.trim() !== '' || activeState.value !== 'all' || searchField.value !== 'all';
});

const searchFieldOptions: Array<{
    value: SearchField;
    label: string;
}> = [
    { value: 'all', label: 'All fields' },
    { value: 'name', label: 'Name' },
    { value: 'email', label: 'Email' },
    { value: 'mobile_number', label: 'Mobile' },
    { value: 'country', label: 'Country' },
];

const stateOptions = computed<Array<{
    value: ClientState;
    label: string;
    count: number;
    helper: string;
    chipClass: string;
}>>(() => [
    {
        value: 'all',
        label: 'All Clients',
        count: props.stats.total,
        helper: 'Full client list',
        chipClass: 'text-primary',
    },
    {
        value: 'pending',
        label: 'Pending',
        count: props.stats.pending,
        helper: 'Need review',
        chipClass: 'text-amber-600 dark:text-amber-400',
    },
    {
        value: 'approved',
        label: 'Approved',
        count: props.stats.approved,
        helper: 'Ready for booking',
        chipClass: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        value: 'banned',
        label: 'Banned',
        count: props.stats.banned,
        helper: 'Need follow-up',
        chipClass: 'text-rose-600 dark:text-rose-400',
    },
]);

const statCards = computed(() => [
    {
        label: 'Total Clients',
        value: props.stats.total,
        helper: 'All client accounts',
        toneClass: 'text-primary',
        panelClass: 'from-primary/10 via-background to-background',
    },
    {
        label: 'Pending Approvals',
        value: props.stats.pending,
        helper: 'Need review now',
        toneClass: 'text-amber-600 dark:text-amber-400',
        panelClass: 'from-amber-500/10 via-background to-background',
    },
    {
        label: 'Approved Clients',
        value: props.stats.approved,
        helper: 'Ready for booking flow',
        toneClass: 'text-emerald-600 dark:text-emerald-400',
        panelClass: 'from-emerald-500/10 via-background to-background',
    },
    {
        label: 'Banned Clients',
        value: props.stats.banned,
        helper: 'Shown for follow-up',
        toneClass: 'text-rose-600 dark:text-rose-400',
        panelClass: 'from-rose-500/10 via-background to-background',
    },
]);

let searchTimer: ReturnType<typeof setTimeout> | undefined;

function fetchClients(pageNumber = 1): void {
    router.get(clientsIndexPath, {
        search: search.value || undefined,
        state: activeState.value === 'all' ? undefined : activeState.value,
        search_field: searchField.value === 'all' ? undefined : searchField.value,
        page: pageNumber,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['clients', 'filters', 'stats', 'flash'],
    });
}

function queueSearch(): void {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        fetchClients(1);
    }, 300);
}

function setState(nextState: ClientState): void {
    activeState.value = nextState;
    fetchClients(1);
}

function clearFilters(): void {
    search.value = '';
    activeState.value = 'all';
    searchField.value = 'all';
    fetchClients(1);
}

function visitPage(url: string): void {
    const pageNumber = Number(new URL(url, window.location.origin).searchParams.get('page') ?? 1);

    fetchClients(pageNumber);
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'N/A';
    }

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: value.includes('T') || value.includes(':') ? 'short' : undefined,
    }).format(new Date(value));
}

function formatGender(value: string | null): string {
    if (!value) {
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

function approveClient(clientId: number): void {
    approvingClientId.value = clientId;

    router.post(approve.form(clientId).action, {}, {
        preserveScroll: true,
        onFinish: () => {
            approvingClientId.value = null;
        },
    });
}

function statusClasses(state: ClientRecord['state']): string {
    if (state === 'pending') {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (state === 'approved') {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    return 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300';
}

function stateLabel(state: ClientRecord['state']): string {
    if (state === 'pending') {
        return 'Pending';
    }

    if (state === 'approved') {
        return 'Approved';
    }

    return 'Banned';
}

const resultSummary = computed(() => {
    const parts: string[] = [];

    if (activeState.value !== 'all') {
        const selectedState = stateOptions.value.find((option) => option.value === activeState.value);

        if (selectedState) {
            parts.push(selectedState.label);
        }
    }

    if (search.value.trim() !== '') {
        const selectedField = searchFieldOptions.find((option) => option.value === searchField.value);
        parts.push(`${selectedField?.label ?? 'All fields'}: "${search.value.trim()}"`);
    }

    if (parts.length === 0) {
        return 'Showing all clients.';
    }

    return `Showing ${parts.join(' • ')}.`;
});

onBeforeUnmount(() => {
    if (searchTimer !== undefined) {
        clearTimeout(searchTimer);
    }
});
</script>

<template>
    <Head title="Manage Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
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

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="item in statCards"
                    :key="item.label"
                    class="rounded-3xl border border-sidebar-border/70 bg-linear-to-br p-4 shadow-sm dark:border-sidebar-border"
                    :class="item.panelClass"
                >
                    <p class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">{{ item.label }}</p>
                    <p :class="['mt-3 text-3xl font-semibold tracking-tight', item.toneClass]">{{ item.value }}</p>
                    <p class="mt-2 text-sm text-muted-foreground">{{ item.helper }}</p>
                </div>
            </div>

            <div class="rounded-3xl border border-sidebar-border/70 bg-background/80 p-4 shadow-sm dark:border-sidebar-border">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-lg font-semibold">Manage Clients</h1>
                        <p class="text-sm text-muted-foreground">
                            Review client status, approve pending accounts, and track follow-up actions.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="rounded-full border border-border/60 bg-background px-3 py-1.5 text-xs text-muted-foreground shadow-sm">
                            {{ resultSummary }}
                        </div>
                        <Button type="button" variant="outline" @click="filtersOpen = !filtersOpen">
                            <Filter class="mr-2 size-4" />
                            Filters
                            <ChevronDown
                                class="ml-2 size-4 transition-transform duration-200"
                                :class="filtersOpen ? 'rotate-180' : ''"
                            />
                        </Button>
                    </div>
                </div>

                <div
                    v-if="filtersOpen"
                    class="mt-4 grid gap-4 border-t border-sidebar-border/70 pt-4 dark:border-sidebar-border xl:grid-cols-[minmax(0,340px)_minmax(0,1fr)]"
                >
                    <div class="rounded-3xl border border-border/60 bg-linear-to-br from-background via-background to-muted/30 p-4 shadow-sm">
                        <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                            <Search class="size-4 text-muted-foreground" />
                            Search and refine
                        </div>
                        <div class="mt-4 space-y-3">
                            <div class="grid gap-3 md:grid-cols-[160px_minmax(0,1fr)]">
                                <div>
                                    <Label for="client-search-field">Search in</Label>
                                    <Select v-model="searchField" @update:model-value="fetchClients(1)">
                                        <SelectTrigger id="client-search-field" class="mt-2 w-full">
                                            <SelectValue placeholder="All fields" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in searchFieldOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label for="client-search">Search clients</Label>
                                    <Input
                                        id="client-search"
                                        v-model="search"
                                        class="mt-2"
                                        :placeholder="searchField === 'all'
                                            ? 'Name, email, mobile, or country'
                                            : `Search by ${searchFieldOptions.find((option) => option.value === searchField)?.label.toLowerCase()}`"
                                        @input="queueSearch"
                                    />
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="hasActiveFilters"
                                    size="sm"
                                    type="button"
                                    variant="outline"
                                    @click="clearFilters"
                                >
                                    <RotateCcw class="mr-1 size-4" />
                                    Clear filters
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-border/60 bg-linear-to-br from-background via-background to-muted/20 p-4 shadow-sm">
                        <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                            <Filter class="size-4 text-muted-foreground" />
                            Client state
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <button
                                v-for="option in stateOptions"
                                :key="option.value"
                                type="button"
                                class="group rounded-2xl border p-4 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:bg-muted/20 hover:shadow-md"
                                :class="activeState === option.value
                                    ? 'border-primary/35 bg-primary/8 ring-1 ring-primary/15'
                                    : 'border-border/60 bg-background/70'"
                                @click="setState(option.value)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-foreground">{{ option.label }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ option.helper }}</p>
                                    </div>
                                    <span :class="['text-2xl font-semibold tracking-tight', option.chipClass]">
                                        {{ option.count }}
                                    </span>
                                </div>
                                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-muted/60">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="activeState === option.value ? 'bg-primary' : 'bg-border/70 group-hover:bg-primary/40'"
                                        :style="{ width: `${Math.max((option.count / Math.max(props.stats.total, 1)) * 100, 12)}%` }"
                                    />
                                </div>
                            </button>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-border/50 pt-4">
                            <Button
                                v-if="exportUrl"
                                as-child
                                size="sm"
                                type="button"
                                variant="outline"
                            >
                                <a :href="exportUrl">
                                    {{ exportLabel }}
                                </a>
                            </Button>
                            <div
                                v-else
                                class="rounded-full border border-dashed border-border/70 px-3 py-1.5 text-xs text-muted-foreground"
                            >
                                Export is available for Pending and Approved filters.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] border border-sidebar-border/70 bg-linear-to-br from-background via-background to-muted/20 shadow-[0_24px_60px_-40px_hsl(var(--foreground)/0.25)] dark:border-sidebar-border">

                <div class="border-b border-sidebar-border/70 bg-[linear-gradient(90deg,hsl(var(--muted)/0.4),hsl(var(--background)),hsl(var(--muted)/0.25))] px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                    Approve is live now. Reject and Ban / Unban are shown for the next workflow stage.
                </div>

                <div v-if="props.clients.data.length === 0" class="px-4 py-12 text-center">
                    <p class="text-sm text-muted-foreground">No clients match the current filter.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead>
                            <tr class="bg-muted/40 text-left">
                                <th class="px-4 py-3 font-medium">Client</th>
                                <th class="px-4 py-3 font-medium">Contact</th>
                                <th class="px-4 py-3 font-medium">Country</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Approval</th>
                                <th class="px-4 py-3 font-medium">Registered At</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr
                                v-for="client in props.clients.data"
                                :key="client.id"
                                class="transition-colors hover:bg-muted/20"
                            >
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1">
                                        <p class="font-medium text-foreground">{{ client.name }}</p>
                                        <p class="text-xs text-muted-foreground">Gender: {{ formatGender(client.gender) }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1">
                                        <p>{{ client.email }}</p>
                                        <p class="text-xs text-muted-foreground">{{ client.mobile_number ?? 'No mobile number' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">{{ client.country ?? 'N/A' }}</td>
                                <td class="px-4 py-4 align-top">
                                    <Badge variant="outline" :class="statusClasses(client.state)">
                                        {{ stateLabel(client.state) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1 rounded-2xl border border-border/50 bg-background/70 px-3 py-2">
                                        <p class="text-foreground">
                                            {{ client.approved_by_name ? `Approved by ${client.approved_by_name}` : 'Not approved yet' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{
                                                client.state === 'banned'
                                                    ? `Banned at ${formatDate(client.banned_at)}`
                                                    : formatDate(client.approved_at)
                                            }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">{{ formatDate(client.created_at) }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button
                                            v-if="client.state === 'pending'"
                                            size="sm"
                                            type="button"
                                            :disabled="approvingClientId === client.id"
                                            @click="approveClient(client.id)"
                                        >
                                            {{ approvingClientId === client.id ? 'Approving...' : 'Approve' }}
                                        </Button>

                                        <div
                                            v-if="client.state === 'pending'"
                                            class="inline-flex items-center rounded-full border border-dashed border-border/70 px-3 py-1 text-xs text-muted-foreground"
                                        >
                                            Reject soon
                                        </div>

                                        <Button
                                            v-if="client.state !== 'pending'"
                                            size="sm"
                                            type="button"
                                            variant="outline"
                                            disabled
                                        >
                                            {{ client.state === 'banned' ? 'Unban' : 'Ban' }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.clients.links.length > 3"
                    class="flex flex-wrap items-center gap-2 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <template v-for="(paginationLink, index) in props.clients.links" :key="`${index}-${paginationLink.label}`">
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
                            @click.prevent="visitPage(paginationLink.url)"
                        >
                            {{ normalizePaginationLabel(paginationLink.label) }}
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
