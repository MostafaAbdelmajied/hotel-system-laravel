<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip
} from 'chart.js';
import { computed } from 'vue';
import { Line, Pie } from 'vue-chartjs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    PointElement,
    LineElement,
);

interface StatisticsData {
    genderData: { gender: string; total: number }[];
    revenueData: { month: number; total: number }[];
    countryData: { country: string; total: number }[];
    topClients: { name: string; total: number }[];
}

const props = defineProps<{
    statisticsData: StatisticsData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Statistics',
        href: '/manager/statistics',
    },
];

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

// 1. Gender Pie Chart
const genderChartData = computed(() => ({
    labels: props.statisticsData.genderData.map((d) =>
        d.gender
            ? d.gender.charAt(0).toUpperCase() + d.gender.slice(1)
            : 'Unknown',
    ),
    datasets: [
        {
            backgroundColor: ['#3b82f6', '#f43f5e', '#94a3b8'],
            data: props.statisticsData.genderData.map((d) => d.total),
        },
    ],
}));

// 2. Revenue Line Chart
const revenueChartData = computed(() => {
    const months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
    ];
    const data = new Array(12).fill(0);

    props.statisticsData.revenueData.forEach((item) => {
        data[item.month - 1] = item.total / 100; // Assuming price is in cents
    });

    return {
        labels: months,
        datasets: [
            {
                label: 'Revenue ($)',
                backgroundColor: '#10b981',
                borderColor: '#10b981',
                data: data,
                tension: 0.4,
                fill: false,
            },
        ],
    };
});

// 3. Countries Pie Chart
const countryChartData = computed(() => ({
    labels: props.statisticsData.countryData.map((d) => d.country),
    datasets: [
        {
            backgroundColor: [
                '#f59e0b',
                '#10b981',
                '#3b82f6',
                '#8b5cf6',
                '#ec4899',
                '#06b6d4',
                '#f97316',
            ],
            data: props.statisticsData.countryData.map((d) => d.total),
        },
    ],
}));

// 4. Top Clients Pie Chart
const topClientsChartData = computed(() => ({
    labels: props.statisticsData.topClients.map((d) => d.name),
    datasets: [
        {
            backgroundColor: [
                '#ef4444',
                '#f97316',
                '#f59e0b',
                '#10b981',
                '#06b6d4',
                '#3b82f6',
                '#6366f1',
                '#8b5cf6',
                '#a855f7',
                '#d946ef',
            ],
            data: props.statisticsData.topClients.map((d) => d.total),
        },
    ],
}));
</script>

<template>
    <Head title="Statistics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Gender Distribution -->
                <Card>
                    <CardHeader>
                        <CardTitle>Gender Distribution</CardTitle>
                        <CardDescription
                            >Percentage of Male vs Female
                            reservations</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="h-75">
                        <Pie :data="genderChartData" :options="chartOptions" />
                    </CardContent>
                </Card>

                <!-- Revenue This Year -->
                <Card>
                    <CardHeader>
                        <CardTitle>Reservations Revenue</CardTitle>
                        <CardDescription
                            >Total revenue generated per month for
                            {{ new Date().getFullYear() }}</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="h-75">
                        <Line
                            :data="revenueChartData"
                            :options="chartOptions"
                        />
                    </CardContent>
                </Card>

                <!-- Reservations by Country -->
                <Card>
                    <CardHeader>
                        <CardTitle>Reservations by Country</CardTitle>
                        <CardDescription
                            >Number of bookings from each
                            country</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="h-75">
                        <Pie :data="countryChartData" :options="chartOptions" />
                    </CardContent>
                </Card>

                <!-- Top 10 Clients -->
                <Card>
                    <CardHeader>
                        <CardTitle>Top 10 Clients</CardTitle>
                        <CardDescription
                            >Clients with the highest number of
                            reservations</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="h-75">
                        <Pie
                            :data="topClientsChartData"
                            :options="chartOptions"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
