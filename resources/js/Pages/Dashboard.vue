<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { defineProps, onMounted, ref } from 'vue';
import { Doughnut, Bar } from 'vue-chartjs'; // Import Doughnut and Bar charts
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement } from 'chart.js'; // Import necessary Chart.js components

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const props = defineProps({
    messages: Object, // Paginated messages
    stats: Object, // Statistics object
    chartData: Object, // Chart data for categories and priorities
});

// Chart data reactive variables
const categoryChartData = ref({
    labels: [],
    datasets: []
});
const priorityChartData = ref({
    labels: [],
    datasets: []
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right', // Or 'top', 'bottom', 'left'
        },
    },
};

onMounted(() => {
    // Populate category chart data
    categoryChartData.value = {
        labels: props.chartData.categories.labels,
        datasets: [
            {
                backgroundColor: [
                    '#41B883', '#E46651', '#00D8FF', '#DD1B16', '#FFD700', '#ADD8E6', '#90EE90', '#FFB6C1', '#808080', '#D3D3D3', '#FFA07A', '#EE82EE'
                ],
                data: props.chartData.categories.data,
            },
        ],
    };

    // Populate priority chart data
    priorityChartData.value = {
        labels: props.chartData.priorities.labels,
        datasets: [
            {
                backgroundColor: [
                    '#228B22', // Green for Low
                    '#FFD700', // Gold for Medium
                    '#FF8C00', // DarkOrange for High
                    '#DC143C', // Crimson for Critical
                ],
                data: props.chartData.priorities.data,
            },
        ],
    };
});

// Helper for status styling
const getStatusClasses = (status) => {
    switch (status) {
        case 'pending_triage': return 'bg-blue-100 text-blue-800';
        case 'triaged': return 'bg-yellow-100 text-yellow-800';
        case 'replied': return 'bg-green-100 text-green-800';
        case 'closed': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Helper for priority styling
const getPriorityClasses = (priority) => {
    switch (priority) {
        case 'low': return 'text-green-600';
        case 'medium': return 'text-yellow-600';
        case 'high': return 'text-orange-600';
        case 'critical': return 'text-red-600';
        default: return 'text-gray-600';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">WhatsApp Triage Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-xl text-gray-800 mb-4">Overall Statistics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-indigo-50 p-4 rounded-lg shadow-sm text-center">
                            <p class="text-3xl font-bold text-indigo-700">{{ props.stats.totalMessages }}</p>
                            <p class="text-sm text-gray-600">Total Messages</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg shadow-sm">
                            <p class="font-bold text-purple-700 mb-2">By Status:</p>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li v-for="(count, status) in props.stats.messagesByStatus" :key="status" class="flex justify-between items-center">
                                    <span :class="getStatusClasses(status)" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ status.replace(/_/g, ' ') }}</span>
                                    <span>{{ count }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="bg-teal-50 p-4 rounded-lg shadow-sm h-64">
                            <p class="font-bold text-teal-700 mb-2">By Category:</p>
                             <div class="h-48">
                                <Doughnut
                                    :data="categoryChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg shadow-sm h-64">
                            <p class="font-bold text-orange-700 mb-2">By Priority:</p>
                            <div class="h-48">
                                <Doughnut
                                    :data="priorityChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                    </div>

                    <h3 class="font-semibold text-xl text-gray-800 mb-4">Latest Incoming Messages</h3>
                    <div v-if="messages.data.length === 0" class="text-center text-gray-500">
                        No new messages to triage.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="message in messages.data" :key="message.id" class="border p-4 rounded-md shadow-sm">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-lg">From: {{ message.from_number }}</h3>
                                <span :class="getStatusClasses(message.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ message.status.replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <p class="text-gray-700">{{ message.message_body.substring(0, 150) }}<span v-if="message.message_body.length > 150">...</span></p>

                            <div v-if="message.classification" class="mt-3 text-sm text-gray-600">
                                <p><strong>Category:</strong> <span class="font-medium text-purple-700">{{ message.classification.category }}</span></p>
                                <p><strong>Priority:</strong> <span :class="getPriorityClasses(message.classification.priority)" class="font-medium">{{ message.classification.priority }}</span></p>
                                <p v-if="message.classification.assigned_team"><strong>Assigned Team:</strong> {{ message.classification.assigned_team.name }}</p>
                            </div>

                            <div class="mt-4 text-right">
                                <Link :href="route('messages.show', message.id)" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    View Details
                                </Link>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <Link
                                v-if="messages.prev_page_url"
                                :href="messages.prev_page_url"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <span v-else class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed">
                                Previous
                            </span>

                            <span class="text-sm text-gray-700">
                                Page {{ messages.current_page }} of {{ messages.last_page }}
                            </span>

                            <Link
                                v-if="messages.next_page_url"
                                :href="messages.next_page_url"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Next
                            </Link>
                            <span v-else class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed">
                                Next
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>