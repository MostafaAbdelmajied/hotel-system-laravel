<script lang="ts" setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import reservations from '@/routes/reservations';
import type { BreadcrumbItem } from '@/types';

type RoomPayload = {
    id: number;
    number: string;
    capacity: number;
    price_cents: number;
    price_in_dollars: string;
    display_price: string;
    floor_name: string | null;
};

type Props = {
    room: RoomPayload;
    selected_dates: {
        check_in: string;
        check_out: string;
    };
    payment: {
        provider: string;
        next_step: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Available Rooms',
        href: '/bookings/available-rooms',
    },
    {
        title: `Reserve Room ${props.room.number}`,
        href: reservations.rooms.show.url({ room: props.room.id }),
    },
];

const form = useForm({
    room_id: props.room.id,
    check_in: props.selected_dates.check_in,
    check_out: props.selected_dates.check_out,
    accompany_number: '0',
});

const page = usePage<{ errors: Record<string, string> }>();
const paymentError = computed(() => page.props.errors?.payment);

const todayDate = new Date().toISOString().split('T')[0];

function submit(): void {
    form.post(reservations.rooms.startPayment.url({ room: props.room.id }), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Reserve Room ${room.number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <section
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h1 class="text-lg font-semibold">Reservation Details</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Review the selected room and booking dates before continuing
                    to payment.
                </p>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border p-4">
                        <p class="text-xs text-muted-foreground uppercase">
                            Room Number
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ room.number }}
                        </p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs text-muted-foreground uppercase">
                            Capacity
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ room.capacity }}
                        </p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs text-muted-foreground uppercase">
                            Price
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ room.display_price }}
                        </p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs text-muted-foreground uppercase">
                            Floor
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ room.floor_name ?? 'N/A' }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 rounded-lg border p-4 text-sm text-muted-foreground"
                >
                    <p>
                        Check in:
                        <span class="font-medium text-foreground">{{
                            selected_dates.check_in
                        }}</span>
                    </p>
                    <p class="mt-1">
                        Check out:
                        <span class="font-medium text-foreground">{{
                            selected_dates.check_out
                        }}</span>
                    </p>
                </div>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h2 class="text-base font-semibold">
                    Start Reservation Payment
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    This step validates your booking details, then proceeds to
                    {{ payment.provider }} checkout.
                </p>
                <p v-if="paymentError" class="mt-3 text-sm text-red-600">
                    {{ paymentError }}
                </p>

                <form
                    class="mt-6 grid gap-4 md:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <input v-model="form.room_id" type="hidden" />

                    <div class="grid gap-2">
                        <Label for="check_in">Check In</Label>
                        <Input
                            id="check_in"
                            v-model="form.check_in"
                            :min="todayDate"
                            required
                            type="date"
                        />
                        <InputError :message="form.errors.check_in" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="check_out">Check Out</Label>
                        <Input
                            id="check_out"
                            v-model="form.check_out"
                            required
                            type="date"
                        />
                        <InputError :message="form.errors.check_out" />
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label for="accompany_number"
                            >Number of accompanying guests</Label
                        >
                        <Input
                            id="accompany_number"
                            v-model="form.accompany_number"
                            min="0"
                            required
                            type="number"
                        />
                        <InputError :message="form.errors.accompany_number" />
                    </div>

                    <div class="md:col-span-2">
                        <Button :disabled="form.processing" type="submit">
                            {{
                                form.processing
                                    ? 'Preparing Payment...'
                                    : 'Continue to Payment'
                            }}
                        </Button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>

