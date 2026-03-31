<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Auth, BreadcrumbItem } from '@/types';
import ManagerFormFields from './Partials/Form.vue';

type ManagerRecord = {
    id: number;
    name: string;
    email: string;
    country: string | null;
    gender: string | null;
    avatar: string | null;
};

type Props = {
    manager: ManagerRecord;
    countries: string[];
};

type PageProps = {
    auth: Auth;
    flash?: {
        success?: string;
        error?: string;
    };
};

type ManagerFormData = {
    name: string;
    email: string;
    country: string;
    gender: string;
    avatar: File | null;
    password: string;
    password_confirmation: string;
};

const props = defineProps<Props>();
const page = usePage<PageProps>();
const managersIndexPath = '/admin/managers';
const editManagerPath = `${managersIndexPath}/${props.manager.id}/edit`;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Managers',
        href: managersIndexPath,
    },
    {
        title: 'Edit Manager',
        href: editManagerPath,
    },
];

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const form = useForm<ManagerFormData>({
    name: props.manager.name,
    email: props.manager.email,
    country: props.manager.country ?? '',
    gender: props.manager.gender ?? '',
    avatar: null,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.put(`${managersIndexPath}/${props.manager.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation', 'avatar');
        },
    });
}
</script>

<template>
    <Head :title="`Edit ${props.manager.name}`" />

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
                <div class="border-b border-sidebar-border/70 px-4 py-4 dark:border-sidebar-border">
                    <h1 class="text-lg font-semibold">Edit Manager</h1>
                    <p class="text-sm text-muted-foreground">
                        Update the selected manager account.
                    </p>
                </div>

                <div class="px-4 py-5">
                    <ManagerFormFields
                        :form="form"
                        :countries="props.countries"
                        :current-avatar="props.manager.avatar"
                        :cancel-href="managersIndexPath"
                        submit-label="Update Manager"
                        :show-password-fields="false"
                        @update:name="form.name = $event"
                        @update:email="form.email = $event"
                        @update:country="form.country = $event"
                        @update:gender="form.gender = $event"
                        @update:avatar="form.avatar = $event"
                        @update:password="form.password = $event"
                        @update:password-confirmation="form.password_confirmation = $event"
                        @submit="submit"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
