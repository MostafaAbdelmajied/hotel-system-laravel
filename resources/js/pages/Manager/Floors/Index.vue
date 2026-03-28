<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Floor } from '@/types';

type PaginatedFloors = {
    data: Floor[];
    current_page?: number;
    last_page?: number;
    total?: number;
};

type Props = {
    floors: Floor[] | PaginatedFloors;
    filters: {
        search?: string;
    };
};

const props = defineProps<Props>();
const search = ref(props.filters.search ?? '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Floors',
        href: '/manager/floors',
    },
];

const floorRows = computed<Floor[]>(() => {
    return Array.isArray(props.floors) ? props.floors : props.floors.data;
});

const filteredFloors = computed<Floor[]>(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return floorRows.value;
    }

    return floorRows.value.filter((floor) => {
        return floor.name.toLowerCase().includes(term) || floor.number.toLowerCase().includes(term);
    });
});
</script>

<template>
    <Head title="Manage Floors" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <h1 class="text-lg font-semibold">Manage Floors</h1>
                    <p class="text-sm text-muted-foreground">
                        Browse available floors and basic metadata.
                    </p>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by floor name or number"
                        class="mt-3 w-full rounded-md border border-sidebar-border/70 px-3 py-2 text-sm outline-none focus:border-ring"
                    >
                </div>

                <div v-if="filteredFloors.length === 0" class="px-4 py-10 text-center">
                    <p class="text-sm text-muted-foreground">No floors found.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead>
                            <tr class="bg-muted/40 text-left">
                                <th class="px-4 py-3 font-medium">ID</th>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Number</th>
                                <th class="px-4 py-3 font-medium">Created By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            <tr v-for="floor in filteredFloors" :key="floor.id">
                                <td class="px-4 py-3">{{ floor.id }}</td>
                                <td class="px-4 py-3">{{ floor.name }}</td>
                                <td class="px-4 py-3">{{ floor.number }}</td>
                                <td class="px-4 py-3">{{ floor.creator?.name ?? floor.created_by ?? 'System' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
