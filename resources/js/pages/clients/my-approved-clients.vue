<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    approvedClients: {
        type: Object,
        required: true,
    },
});

const page = usePage();

const breadcrumbs = [
    {
        title: 'My Approved Clients',
        href: '/clients/my-approved',
    },
];

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const formatDate = (value) => {
    if (!value) {
        return 'N/A';
    }

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const formatGender = (value) => {
    if (!value) {
        return 'N/A';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
};
</script>

<template>
    <Head title="My Approved Clients" />

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
                <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <h1 class="text-lg font-semibold">My Approved Clients</h1>
                    <p class="text-sm text-muted-foreground">
                        Clients that were approved by your account.
                    </p>
                </div>

                <div v-if="props.approvedClients.data.length === 0" class="px-4 py-10 text-center">
                    <p class="text-sm text-muted-foreground">You have not approved any clients yet.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead>
                            <tr class="bg-muted/40 text-left">
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium">Mobile number</th>
                                <th class="px-4 py-3 font-medium">Country</th>
                                <th class="px-4 py-3 font-medium">Gender</th>
                                <th class="px-4 py-3 font-medium">Approved At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr v-for="client in props.approvedClients.data" :key="client.id">
                                <td class="px-4 py-3">{{ client.name }}</td>
                                <td class="px-4 py-3">{{ client.email }}</td>
                                <td class="px-4 py-3">{{ client.mobile_number }}</td>
                                <td class="px-4 py-3">{{ client.country }}</td>
                                <td class="px-4 py-3">{{ formatGender(client.gender) }}</td>
                                <td class="px-4 py-3">{{ formatDate(client.approved_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.approvedClients.links.length > 3"
                    class="flex flex-wrap items-center gap-2 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <template v-for="(paginationLink, index) in props.approvedClients.links" :key="`${index}-${paginationLink.label}`">
                        <span
                            v-if="!paginationLink.url"
                            class="rounded-md border px-3 py-1.5 text-xs text-muted-foreground"
                            v-html="paginationLink.label"
                        />
                        <Link
                            v-else
                            :href="paginationLink.url"
                            class="rounded-md border px-3 py-1.5 text-xs"
                            :class="paginationLink.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            preserve-scroll
                            v-html="paginationLink.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
