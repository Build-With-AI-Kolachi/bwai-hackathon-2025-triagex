<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    articles: Array,
});

const deleteArticle = (id) => {
    if (confirm('Are you sure you want to delete this article?')) {
        router.delete(route('knowledge-base.destroy', id), {
            onSuccess: () => {
                // Optionally show a success message
            },
        });
    }
};
</script>

<template>
    <Head title="Knowledge Base" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Knowledge Base Articles</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-end mb-4">
                        <Link :href="route('knowledge-base.create')" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add New Article
                        </Link>
                    </div>

                    <div v-if="articles.length === 0" class="text-center text-gray-500">
                        No knowledge base articles found.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="article in articles" :key="article.id" class="border p-4 rounded-md shadow-sm">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-lg">{{ article.title }}</h3>
                                <div class="flex space-x-2">
                                    <Link :href="route('knowledge-base.edit', article.id)" class="px-3 py-1 bg-yellow-500 text-white text-xs rounded-md hover:bg-yellow-600">Edit</Link>
                                    <button @click="deleteArticle(article.id)" class="px-3 py-1 bg-red-600 text-white text-xs rounded-md hover:bg-red-700">Delete</button>
                                </div>
                            </div>
                            <p class="text-gray-700 text-sm">{{ article.content.substring(0, 200) }}<span v-if="article.content.length > 200">...</span></p>
                            <p class="text-xs text-gray-500 mt-2">Category: {{ article.category ?? 'N/A' }} | Active: {{ article.is_active ? 'Yes' : 'No' }}</p>
                            <p v-if="article.keywords" class="text-xs text-gray-500 mt-1">Keywords: {{ article.keywords.join(', ') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>