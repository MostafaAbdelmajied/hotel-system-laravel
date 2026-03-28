<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, defineProps, ref } from 'vue';
import { Auth, Floor } from '@/types';
import { ColumnDef, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
type PageProps = {
    auth: Auth;
    flash: {
        success?: string;
        error?: string;
    };
};
type Props ={
    floors:Floor[];
    filters:{
        search?:string;
    };
};
type FloorFormData={
    name:string;
    number:string;
};

const props = defineProps<Props>();
const page = usePage<PageProps>();
const breadCrumbs[] = [
    {
        title: 'Manage Floors',
        href: '/manager/floors',
    },
];
const auth = computed(page.props.auth);
const flashSuccess = computed(() => page.props.flash.success);
const flashError = computed(()=>page.props.flash.error);
const currentUserId =computed(()=>auth.value.user.id);
const isAdmin= computed(()=>auth.value.isAdmin ?? false);
const search = ref(props.filters.search ?? '');
const form = useForm<FloorFormData>(
    {
        name: '',
        number: '',
    },

);
const columns = computed<ColumnDef<Floor>[]>(() => {
    const baseColumns: ColumnDef<Floor>[] = [
        {
            accessorKey:'name',
            header:'Name',
            cell:({row})=>row.original.name
        },
        {
            accessorKey:'number',
            header:'Number',
            cell:({row})=>row.original.name
        },
        {
            accessorKey:"created_by",
            header:"Created By",
            cell:({row})=>row.original.created_by,
        }
    ];
        if(isAdmin){
            baseColumns.push(
                {
                    accessorKey:'manager_name',
                    header:'Manager Name',
                    // cell:({row})=>row.original.
                }
            )
        }
        return baseColumns;
});
const table = useVueTable(
    {
        get data(){
            return props.floors.data;
        },
        get columns() {
            return columns.value;
        },
getCoreRowModel:getCoreRowModel(),
        manualPagination: true,
        get pageCount(){
            return props.floors.last_page;
        }
    }
)
</script>
<template>
    <Head title="Manage Floors" />
    <table>
        <thead>
            <th>id</th>
            <th>name</th>
            <th>number</th>
            <th>created_by</th>
            <th v-if="$page.props.auth.user.roles.includes('Admin')">
                Manager Name
            </th>
        </thead>
        <tbody>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tbody>
    </table>
</template>
