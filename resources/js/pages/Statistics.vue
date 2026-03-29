<script lang="ts" setup>
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
import { Card } from '@/components/ui/card';
// import { Bar, Line, Pie } from 'vue-chartjs';

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
</script>

<template>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <Card> <!-- Gender Pie Chart --> </Card>
        <Card> <!-- Revenue Line Chart --> </Card>
        <Card> <!-- Countries Pie Chart --> </Card>
        <Card> <!-- Top Clients Pie Chart --> </Card>
    </div>

    <slot />
</template>

<style scoped></style>
