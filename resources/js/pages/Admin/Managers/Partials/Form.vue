<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ManagerFormData = {
    name: string;
    email: string;
    country: string;
    gender: string;
    avatar: File | null;
    password: string;
    password_confirmation: string;
};

type Props = {
    form: InertiaForm<ManagerFormData>;
    countries: string[];
    submitLabel: string;
    cancelHref: string;
    showPasswordFields: boolean;
    currentAvatar?: string | null;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    submit: [];
    'update:name': [value: string];
    'update:email': [value: string];
    'update:country': [value: string];
    'update:gender': [value: string];
    'update:avatar': [value: File | null];
    'update:password': [value: string];
    'update:passwordConfirmation': [value: string];
}>();

const name = computed({
    get: () => props.form.name,
    set: (value: string) => emit('update:name', value),
});

const email = computed({
    get: () => props.form.email,
    set: (value: string) => emit('update:email', value),
});

const country = computed({
    get: () => props.form.country,
    set: (value: string) => emit('update:country', value),
});

const gender = computed({
    get: () => props.form.gender,
    set: (value: string) => emit('update:gender', value),
});

const password = computed({
    get: () => props.form.password,
    set: (value: string) => emit('update:password', value),
});

const passwordConfirmation = computed({
    get: () => props.form.password_confirmation,
    set: (value: string) => emit('update:passwordConfirmation', value),
});

function handleAvatarChange(event: Event): void {
    const target = event.target as HTMLInputElement;

    emit('update:avatar', target.files?.[0] ?? null);
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="name" autocomplete="name" name="name" placeholder="Manager name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="email" autocomplete="email" name="email" placeholder="manager@example.com" type="email" />
                <InputError :message="form.errors.email" />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="country">Country</Label>
                <select
                    id="country"
                    v-model="country"
                    name="country"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="" disabled>Select country</option>
                    <option
                        v-for="country in countries"
                        :key="country"
                        :value="country"
                        class="bg-background text-foreground"
                    >
                        {{ country }}
                    </option>
                </select>
                <InputError :message="form.errors.country" />
            </div>

            <div class="grid gap-2">
                <Label for="gender">Gender</Label>
                <select
                    id="gender"
                    v-model="gender"
                    name="gender"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="" disabled>Select gender</option>
                    <option value="male" class="bg-background text-foreground">Male</option>
                    <option value="female" class="bg-background text-foreground">Female</option>
                </select>
                <InputError :message="form.errors.gender" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="avatar">Avatar</Label>
            <Input
                id="avatar"
                accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                name="avatar"
                type="file"
                @change="handleAvatarChange"
            />
            <p v-if="currentAvatar" class="text-xs text-muted-foreground">
                Current avatar is already uploaded.
            </p>
            <InputError :message="form.errors.avatar" />
        </div>

        <div class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="mb-4">
                <h2 class="text-sm font-medium text-foreground">Password</h2>
                <p class="text-sm text-muted-foreground">
                    {{ showPasswordFields ? 'Set the password for the new manager account.' : 'Leave blank to keep the current password.' }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        v-model="password"
                        :required="showPasswordFields"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="passwordConfirmation"
                        :required="showPasswordFields"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Button as-child type="button" variant="outline">
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button :disabled="form.processing" type="submit">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
