<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />

    <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">
        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                WhatsApp Triage Assistant
            </h1>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Welcome to the GenAI-powered WhatsApp Triage Assistant.
            </p>
        </div>
    </div>
</template>