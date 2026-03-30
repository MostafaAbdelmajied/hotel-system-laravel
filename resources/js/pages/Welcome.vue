<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const quickStats = [
    {
        value: 'Approval-first',
        label: 'Client onboarding stays controlled before anyone reaches protected flows.',
    },
    {
        value: 'Rooms + Floors',
        label: 'Inventory, pricing, capacity, and booking availability are managed in one place.',
    },
    {
        value: 'Role-based',
        label: 'Admin, manager, receptionist, and client journeys stay separated and predictable.',
    },
];

const modules = [
    {
        title: 'Client approval workflow',
        copy: 'Clients register, remain pending, then move forward only after the right staff member approves them.',
    },
    {
        title: 'Room inventory control',
        copy: 'Managers keep room capacity, price, and floor assignment organized with constraints that protect reservations.',
    },
    {
        title: 'Reservation-ready operations',
        copy: 'Available rooms are filtered by date range so the booking flow starts from actual availability, not guesswork.',
    },
];

const workflow = [
    'Client creates an account',
    'Staff reviews and approves',
    'Approved user searches available rooms',
    'Reservation and payment complete the stay',
];
</script>

<template>
    <Head title="Hotel System">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=fraunces:600,700,800|manrope:400,500,600,700,800"
            rel="stylesheet"
        />
    </Head>

    <div
        class="min-h-screen overflow-hidden bg-[#f4efe6] text-[#132238] [font-family:'Manrope',sans-serif] dark:bg-[#0d1726] dark:text-[#f6efe3]"
    >
        <div class="relative isolate">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-[34rem] bg-[radial-gradient(circle_at_top_left,_rgba(213,164,86,0.28),_transparent_45%),radial-gradient(circle_at_top_right,_rgba(29,78,216,0.18),_transparent_36%),linear-gradient(180deg,_rgba(255,255,255,0.75),_transparent)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(212,168,91,0.18),_transparent_42%),radial-gradient(circle_at_top_right,_rgba(56,189,248,0.12),_transparent_34%),linear-gradient(180deg,_rgba(15,23,42,0.92),_transparent)]"
            />
            <div
                class="pointer-events-none absolute left-[8%] top-28 h-40 w-40 rounded-full bg-[#d7b46f]/40 blur-3xl motion-safe:animate-pulse dark:bg-[#c28f32]/20"
            />
            <div
                class="pointer-events-none absolute right-[10%] top-20 h-52 w-52 rounded-full bg-[#3b82f6]/15 blur-3xl motion-safe:animate-pulse dark:bg-[#38bdf8]/10"
            />

            <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 lg:px-10">
                <header class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/60 bg-white/70 text-sm font-semibold text-[#8a5a16] shadow-sm shadow-[#d6b06b]/20 backdrop-blur dark:border-white/10 dark:bg-white/5 dark:text-[#e6ba67]"
                        >
                            HS
                        </div>
                        <div>
                            <p
                                class="[font-family:'Fraunces',serif] text-lg font-semibold tracking-[0.01em]"
                            >
                                Hotel System
                            </p>
                            <p class="text-xs uppercase tracking-[0.28em] text-[#7e6853] dark:text-[#b8a892]">
                                Staff Approval • Rooms • Reservations
                            </p>
                        </div>
                    </div>

                    <nav class="flex items-center gap-2 sm:gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            prefetch
                            class="inline-flex items-center rounded-full border border-[#173253]/15 bg-[#173253] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#101f37] dark:border-white/10 dark:bg-[#f5d08b] dark:text-[#101826] dark:hover:bg-[#ebc269]"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="login()"
                                prefetch
                                class="inline-flex items-center rounded-full border border-[#173253]/15 bg-white/80 px-5 py-2 text-sm font-semibold text-[#173253] transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-[#f7e7c2] dark:hover:bg-white/10"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="register()"
                                prefetch
                                class="inline-flex items-center rounded-full border border-[#d3a24f] bg-[#d3a24f] px-5 py-2 text-sm font-semibold text-[#142338] transition hover:bg-[#be913f] dark:border-[#f0c979] dark:bg-[#f0c979] dark:text-[#101826] dark:hover:bg-[#e4bb66]"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="flex flex-1 items-center py-10 lg:py-14">
                    <div class="grid w-full gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                        <section class="space-y-8">
                            <div
                                class="inline-flex items-center rounded-full border border-[#d8c6ad] bg-white/70 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.28em] text-[#7a5e34] backdrop-blur dark:border-white/10 dark:bg-white/5 dark:text-[#efcc87]"
                            >
                                Calm hotel operations
                            </div>

                            <div class="space-y-5">
                                <h1
                                    class="max-w-3xl [font-family:'Fraunces',serif] text-5xl leading-[1.02] font-semibold tracking-tight sm:text-6xl lg:text-7xl"
                                >
                                    One place to run approvals, rooms, and reservations with confidence.
                                </h1>

                                <p class="max-w-2xl text-base leading-8 text-[#4d5d74] sm:text-lg dark:text-[#c7d2e0]">
                                    This hotel system keeps the flow organized from the first client signup
                                    to the final room reservation. Staff approvals, role-based access, room
                                    control, and booking readiness all live inside one focused workspace.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <Link
                                    v-if="$page.props.auth.user"
                                    :href="dashboard()"
                                    prefetch
                                    class="inline-flex items-center rounded-full bg-[#173253] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#173253]/15 transition hover:-translate-y-0.5 hover:bg-[#101f37] dark:bg-[#f5d08b] dark:text-[#0f1726] dark:hover:bg-[#ebc269]"
                                >
                                    Open dashboard
                                </Link>

                                <template v-else>
                                    <Link
                                        :href="login()"
                                        prefetch
                                        class="inline-flex items-center rounded-full bg-[#173253] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#173253]/15 transition hover:-translate-y-0.5 hover:bg-[#101f37] dark:bg-[#f5d08b] dark:text-[#0f1726] dark:hover:bg-[#ebc269]"
                                    >
                                        Start with login
                                    </Link>

                                    <Link
                                        v-if="canRegister"
                                        :href="register()"
                                        prefetch
                                        class="inline-flex items-center rounded-full border border-[#173253]/15 bg-white/80 px-6 py-3 text-sm font-semibold text-[#173253] transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-[#f7e7c2] dark:hover:bg-white/10"
                                    >
                                        Create client account
                                    </Link>
                                </template>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article
                                    v-for="stat in quickStats"
                                    :key="stat.value"
                                    class="rounded-[1.75rem] border border-white/70 bg-white/70 p-5 shadow-[0_22px_60px_-38px_rgba(15,23,42,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/5 dark:shadow-none"
                                >
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[#8a5a16] dark:text-[#efcc87]">
                                        {{ stat.value }}
                                    </p>
                                    <p class="mt-3 text-sm leading-7 text-[#506177] dark:text-[#c2cedb]">
                                        {{ stat.label }}
                                    </p>
                                </article>
                            </div>
                        </section>

                        <section class="relative">
                            <div
                                class="absolute -right-6 -top-6 h-28 w-28 rounded-full border border-white/40 bg-white/30 blur-2xl dark:border-white/5 dark:bg-white/5"
                            />

                            <div
                                class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-[#173253] p-6 text-white shadow-[0_34px_80px_-36px_rgba(15,23,42,0.65)] dark:border-white/10 dark:bg-[#132033]"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.3em] text-[#d7c4a1]">
                                            Operations snapshot
                                        </p>
                                        <h2
                                            class="mt-3 [font-family:'Fraunces',serif] text-3xl leading-tight font-semibold text-[#fff8ea]"
                                        >
                                            A smoother flow for both staff and guests
                                        </h2>
                                    </div>

                                    <div
                                        class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-[#f4d288]"
                                    >
                                        Live-ready
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-[1.4rem] bg-white/8 p-4 ring-1 ring-white/10">
                                        <p class="text-xs uppercase tracking-[0.24em] text-[#d7c4a1]">
                                            Core modules
                                        </p>
                                        <div class="mt-4 space-y-3">
                                            <article
                                                v-for="module in modules"
                                                :key="module.title"
                                                class="rounded-2xl bg-white/6 p-4"
                                            >
                                                <h3 class="text-sm font-semibold text-[#fff7e8]">
                                                    {{ module.title }}
                                                </h3>
                                                <p class="mt-2 text-sm leading-7 text-[#d5deea]">
                                                    {{ module.copy }}
                                                </p>
                                            </article>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.4rem] bg-[#f3e0b8] p-4 text-[#132238]">
                                        <p class="text-xs uppercase tracking-[0.24em] text-[#8a5a16]">
                                            Typical journey
                                        </p>
                                        <ol class="mt-4 space-y-3">
                                            <li
                                                v-for="(step, index) in workflow"
                                                :key="step"
                                                class="flex items-start gap-3 rounded-2xl bg-white/60 p-4"
                                            >
                                                <span
                                                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#173253] text-xs font-semibold text-white"
                                                >
                                                    0{{ index + 1 }}
                                                </span>
                                                <span class="text-sm font-medium leading-6">
                                                    {{ step }}
                                                </span>
                                            </li>
                                        </ol>

                                        <div
                                            class="mt-4 rounded-2xl border border-[#173253]/10 bg-white/70 p-4"
                                        >
                                            <p class="text-xs uppercase tracking-[0.24em] text-[#8a5a16]">
                                                Why it works
                                            </p>
                                            <p class="mt-2 text-sm leading-7 text-[#37475d]">
                                                Every role sees only what it needs, while the booking
                                                flow always starts from validated room availability.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </main>

                <footer
                    class="flex flex-col gap-3 border-t border-[#d8c6ad] pt-6 text-sm text-[#66768d] dark:border-white/10 dark:text-[#9fb0c4] md:flex-row md:items-center md:justify-between"
                >
                    <p>Built for hotel teams that need approvals, inventory control, and dependable reservation flow.</p>
                    <p class="uppercase tracking-[0.28em] text-[#8a5a16] dark:text-[#efcc87]">
                        Laravel • Inertia • Vue • MySQL
                    </p>
                </footer>
            </div>
        </div>
    </div>
</template>
