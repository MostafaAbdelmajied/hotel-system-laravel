<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    countries: string[];
}>();

const form = useForm<{
    name: string;
    email: string;
    country: string;
    gender: string;
    avatar: File | null;
    password: string;
    password_confirmation: string;
}>({
    name: '',
    email: '',
    country: '',
    gender: '',
    avatar: null,
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.transform((data) => {
        const payload = { ...data };

        if (!payload.avatar) {
            delete payload.avatar;
        }

        return payload;
    }).post(store().url, {
        forceFormData: true,
        onSuccess: () => form.reset('password', 'password_confirmation', 'avatar'),
    });
};

const handleAvatarChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    form.avatar = target.files?.[0] ?? null;
};
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        v-model="form.name"
                        placeholder="Full name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="country">Country</Label>
                    <select
                        id="country"
                        v-model="form.country"
                        name="country"
                        required
                        :tabindex="3"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="" disabled selected class="bg-background text-foreground">Select country</option>
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
                        v-model="form.gender"
                        name="gender"
                        required
                        :tabindex="4"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="" disabled selected class="bg-background text-foreground">Select gender</option>
                        <option value="male" class="bg-background text-foreground">Male</option>
                        <option value="female" class="bg-background text-foreground">Female</option>
                    </select>
                    <InputError :message="form.errors.gender" />
                </div>

                <div class="grid gap-2">
                    <Label for="avatar">Avatar (optional)</Label>
                    <Input
                        id="avatar"
                        type="file"
                        name="avatar"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        :tabindex="5"
                        @change="handleAvatarChange"
                    />
                    <InputError :message="form.errors.avatar" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="6"
                        autocomplete="new-password"
                        name="password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        required
                        :tabindex="7"
                        autocomplete="new-password"
                        name="password_confirmation"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="8"
                    :disabled="form.processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="form.processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="9"
                    >Log in</TextLink
                >
            </div>
        </form>
    </AuthBase>
</template>
