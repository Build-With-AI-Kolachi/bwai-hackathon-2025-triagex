<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { defineProps, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    message: Object,
    teams: Array,
    categories: Array,
    priorities: Array,
});

const showReclassifyForm = ref(false);
const showReplyForm = ref(false);
const replyDraft = ref('');
const suggestedTags = ref([]);
const selectedTone = ref('business-friendly');
const sendingReply = ref(false);
const replySentMessage = ref('');

const reclassifyForm = useForm({
    category: props.message.classification?.category || props.categories[0],
    priority: props.message.classification?.priority || props.priorities[1], // default to medium
    assigned_team_id: props.message.classification?.assigned_team_id || null,
    reasoning: props.message.classification?.gemini_reasoning || '',
});

const updateStatusForm = useForm({
    status: props.message.status,
});

watch(() => props.message.status, (newStatus) => {
    updateStatusForm.status = newStatus;
});

const submitReclassification = () => {
    reclassifyForm.post(route('messages.reclassify', props.message.id), {
        onSuccess: () => {
            router.reload({ only: ['message'] });
            showReclassifyForm.value = false;
            // Optionally show a success message
        },
        onError: (errors) => {
            console.error('Reclassification error:', errors);
        }
    });
};

const suggestReply = async () => {
    replyDraft.value = 'Generating reply...';
    suggestedTags.value = [];
    try {
        const response = await axios.post(route('messages.suggestReply', props.message.id), {
            tone: selectedTone.value
        });
        replyDraft.value = response.data.reply_draft;
        suggestedTags.value = response.data.suggested_tags;
    } catch (error) {
        console.error('Error suggesting reply:', error);
        replyDraft.value = 'Failed to generate reply. Please try again.';
        suggestedTags.value = [];
    }
};

const sendReply = async () => {
    if (!replyDraft.value.trim()) {
        alert('Reply content cannot be empty.');
        return;
    }
    sendingReply.value = true;
    replySentMessage.value = '';
    try {
        await axios.post(route('messages.sendReply', props.message.id), {
            reply_content: replyDraft.value
        });
        replySentMessage.value = 'Reply sent successfully!';
        router.reload({ only: ['message'] }); // Reload message to reflect status change
        showReplyForm.value = false;
        replyDraft.value = '';
    } catch (error) {
        console.error('Error sending reply:', error);
        replySentMessage.value = 'Failed to send reply.';
    } finally {
        sendingReply.value = false;
    }
};

const updateMessageStatus = () => {
    updateStatusForm.post(route('messages.updateStatus', props.message.id), {
        preserveScroll: true,
        onSuccess: () => {
            // No explicit reload needed, message prop is reactive via watch
        },
        onError: (errors) => {
            console.error('Status update error:', errors);
        }
    });
};
</script>

<template>
    <Head :title="`Message from ${message.from_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Message Details</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <Link :href="route('dashboard')" class="text-blue-600 hover:underline mb-4 block">&larr; Back to Dashboard</Link>

                    <h3 class="text-2xl font-bold mb-4">Message from {{ message.from_number }}</h3>
                    <p class="text-gray-700 mb-4">{{ message.message_body }}</p>
                    <p class="text-sm text-gray-500 mb-6">Received at: {{ new Date(message.created_at).toLocaleString() }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-lg mb-2">Current Classification</h4>
                            <div v-if="message.classification" class="border p-4 rounded-md bg-blue-50">
                                <p><strong>Category:</strong> <span class="font-medium text-purple-700">{{ message.classification.category }}</span></p>
                                <p><strong>Priority:</strong> <span :class="{
                                    'text-green-600': message.classification.priority === 'low',
                                    'text-yellow-600': message.classification.priority === 'medium',
                                    'text-orange-600': message.classification.priority === 'high',
                                    'text-red-600': message.classification.priority === 'critical',
                                }" class="font-medium">{{ message.classification.priority }}</span></p>
                                <p v-if="message.classification.assigned_team"><strong>Assigned Team:</strong> {{ message.classification.assigned_team.name }}</p>
                                <p v-if="message.classification.confidence_score !== null"><strong>Confidence:</strong> {{ (message.classification.confidence_score * 100).toFixed(2) }}%</p>
                                <p v-if="message.classification.gemini_reasoning"><strong>Gemini Reasoning:</strong> {{ message.classification.gemini_reasoning }}</p>
                                <p class="text-sm text-gray-500 mt-2">Status: {{ message.classification.status.replace(/_/g, ' ') }}</p>

                                <button @click="showReclassifyForm = !showReclassifyForm" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                    {{ showReclassifyForm ? 'Hide Reclassification' : 'Reclassify / Assign' }}
                                </button>
                            </div>
                            <div v-else class="text-gray-500">
                                No classification available yet.
                                <button @click="showReclassifyForm = !showReclassifyForm" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                    {{ showReclassifyForm ? 'Hide Reclassification' : 'Classify / Assign' }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-lg mb-2">Message Status</h4>
                            <form @submit.prevent="updateMessageStatus" class="flex items-center space-x-2">
                                <select v-model="updateStatusForm.status" class="block w-full mt-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="pending_triage">Pending Triage</option>
                                    <option value="triaged">Triaged</option>
                                    <option value="replied">Replied</option>
                                    <option value="closed">Closed</option>
                                </select>
                                <button type="submit" :disabled="updateStatusForm.processing" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                                    Update
                                </button>
                            </form>
                            <p v-if="updateStatusForm.recentlySuccessful" class="text-sm text-green-600 mt-2">Status updated!</p>
                        </div>
                    </div>

                    <div v-if="showReclassifyForm" class="mt-8 p-6 border rounded-md bg-gray-50">
                        <h4 class="font-semibold text-lg mb-4">Reclassify / Manually Assign</h4>
                        <form @submit.prevent="submitReclassification">
                            <div class="mb-4">
                                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                                <select id="category" v-model="reclassifyForm.category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                                <div v-if="reclassifyForm.errors.category" class="text-red-600 text-sm mt-1">{{ reclassifyForm.errors.category }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                                <select id="priority" v-model="reclassifyForm.priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option v-for="prio in priorities" :key="prio" :value="prio">{{ prio }}</option>
                                </select>
                                <div v-if="reclassifyForm.errors.priority" class="text-red-600 text-sm mt-1">{{ reclassifyForm.errors.priority }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="assigned_team" class="block text-sm font-medium text-gray-700">Assign to Team</label>
                                <select id="assigned_team" v-model="reclassifyForm.assigned_team_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option :value="null">-- Select Team --</option>
                                    <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                                </select>
                                <div v-if="reclassifyForm.errors.assigned_team_id" class="text-red-600 text-sm mt-1">{{ reclassifyForm.errors.assigned_team_id }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="reasoning" class="block text-sm font-medium text-gray-700">Reasoning (Optional)</label>
                                <textarea id="reasoning" v-model="reclassifyForm.reasoning" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                <div v-if="reclassifyForm.errors.reasoning" class="text-red-600 text-sm mt-1">{{ reclassifyForm.errors.reasoning }}</div>
                            </div>

                            <button type="submit" :disabled="reclassifyForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                Update Classification
                            </button>
                        </form>
                    </div>

                    <div class="mt-8 p-6 border rounded-md bg-white">
                        <h4 class="font-semibold text-lg mb-4">Reply to Customer</h4>
                        <button @click="showReplyForm = !showReplyForm" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                            {{ showReplyForm ? 'Hide Reply Section' : 'Show Reply Section' }}
                        </button>

                        <div v-if="showReplyForm" class="mt-4">
                            <div class="mb-4">
                                <label for="tone" class="block text-sm font-medium text-gray-700">Reply Tone</label>
                                <select id="tone" v-model="selectedTone" @change="replyDraft = ''; suggestedTags = [];" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="business-friendly">Business-Friendly</option>
                                    <option value="technical">Technical</option>
                                </select>
                            </div>

                            <button @click="suggestReply" :disabled="replyDraft === 'Generating reply...'" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 disabled:opacity-50">
                                Generate Draft Reply
                            </button>

                            <div v-if="replyDraft" class="mt-4">
                                <label for="reply_draft" class="block text-sm font-medium text-gray-700">Suggested Reply Draft:</label>
                                <textarea id="reply_draft" v-model="replyDraft" rows="8" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>

                                <div v-if="suggestedTags.length" class="mt-2 text-sm text-gray-600">
                                    <strong>Suggested Tags:</strong>
                                    <span v-for="tag in suggestedTags" :key="tag" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800 mr-1">
                                        {{ tag.trim() }}
                                    </span>
                                </div>

                                <button @click="sendReply" :disabled="sendingReply" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                                    {{ sendingReply ? 'Sending...' : 'Send Reply via WhatsApp' }}
                                </button>
                                <p v-if="replySentMessage" :class="{'text-green-600': replySentMessage.includes('successfully'), 'text-red-600': replySentMessage.includes('failed')}" class="mt-2 text-sm">{{ replySentMessage }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>