<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    categories: Array,
    priorities: Array,
    teams: Object, // Map of team_id => team_name
});

const messageInput = ref('');
const selectedTone = ref('business-friendly');
const processing = ref(false);
const result = ref(null);
const error = ref(null);

const submitMessage = async () => {
    processing.value = true;
    result.value = null;
    error.value = null;

    try {
        const response = await axios.post(route('gemini.test.process'), {
            message_body: messageInput.value,
            tone: selectedTone.value,
        });
        result.value = response.data;
    } catch (err) {
        console.error('Error processing message:', err);
        error.value = err.response?.data?.message || 'An unknown error occurred.';
    } finally {
        processing.value = false;
    }
};

// Helper to display team name from ID
const getTeamName = (teamId) => {
    return props.teams[teamId] || 'N/A';
};
</script>

<template>
    <Head title="Gemini Test UI" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gemini Triage Test UI</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="mb-4 text-gray-600">Enter a message below to see how Gemini classifies it, suggests replies, and interacts with the dummy knowledge base.</p>

                        <form @submit.prevent="submitMessage" class="space-y-4">
                            <div>
                                <label for="messageInput" class="block text-sm font-medium text-gray-700">Enter WhatsApp Message:</label>
                                <textarea id="messageInput" v-model="messageInput" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g., My API key is not working, getting a 401 error. Is there an issue?"></textarea>
                            </div>

                            <!-- <div>
                                <label for="toneSelect" class="block text-sm font-medium text-gray-700">Select Reply Tone:</label>
                                <select id="toneSelect" v-model="selectedTone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="business-friendly">Business-Friendly</option>
                                    <option value="technical">Technical</option>
                                </select>
                            </div> -->

                            <button type="submit" :disabled="processing || !messageInput.trim()" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                                {{ processing ? 'Processing...' : 'Process with Gemini' }}
                            </button>
                        </form>

                        <div v-if="error" class="mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            <p class="font-bold">Error:</p>
                            <p>{{ error }}</p>
                        </div>

                        <div v-if="result" class="mt-6 space-y-6">
                            <div class="border p-4 rounded-md bg-green-50">
                                <h3 class="font-semibold text-lg mb-2 text-green-800">Gemini Classification:</h3>
                                <p><strong>Category:</strong> <span class="font-medium text-purple-700">{{ result.classification.category }}</span></p>
                                <p><strong>Priority:</strong> <span :class="{
                                        'text-green-600': result.classification.priority === 'low',
                                        'text-yellow-600': result.classification.priority === 'medium',
                                        'text-orange-600': result.classification.priority === 'high',
                                        'text-red-600': result.classification.priority === 'critical',
                                    }" class="font-medium">{{ result.classification.priority }}</span></p>
                                <p v-if="result.classification.confidence_score !== null"><strong>Confidence:</strong> {{ (result.classification.confidence_score * 100).toFixed(2) }}%</p>
                                <p><strong>Reasoning:</strong> {{ result.classification.reasoning }}</p>
                                <p><strong>Assigned Team (Predicted):</strong> {{ result.assigned_team }}</p>
                            </div>

                            <div class="border p-4 rounded-md bg-yellow-50">
                                <h3 class="font-semibold text-lg mb-2 text-yellow-800">Relevant Knowledge Base Articles:</h3>
                                <div v-if="result.relevant_articles.length === 0" class="text-gray-600">
                                    No highly relevant articles found for this query.
                                </div>
                                <ul v-else class="list-disc list-inside space-y-1">
                                    <li v-for="article in result.relevant_articles" :key="article.id">
                                        <strong class="text-blue-700">{{ article.title }}</strong> (Category: {{ article.category }})
                                        <p class="text-sm text-gray-700 italic">{{ article.content.substring(0, 100) }}...</p>
                                    </li>
                                </ul>
                            </div>

                            <div class="border p-4 rounded-md bg-blue-50">
                                <h3 class="font-semibold text-lg mb-2 text-blue-800">Suggested Reply ({{ selectedTone }} tone):</h3>
                                <div class="whitespace-pre-wrap p-2 border rounded bg-white text-gray-800"
                                v-html="result.reply_suggestions.reply_draft.replaceAll('\\n', '<br>')">
                                </div>
                                <!-- <textarea v-model="result.reply_suggestions.reply_draft" class="w-full h-24 border rounded p-2 whitespace-pre-wrap"></textarea> -->
                                <div class="mt-3 text-sm text-gray-700">
                                    <strong>Suggested Tags for Documentation Improvement:</strong>
                                    <span v-if="result.reply_suggestions.suggested_tags && result.reply_suggestions.suggested_tags.length > 0">
                                        <span v-for="tag in result.reply_suggestions.suggested_tags" :key="tag" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800 mr-1 mt-1">
                                            <div class="" v-html="tag.trim()"></div>
                                        </span>
                                    </span>
                                    <span v-else>None suggested.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>