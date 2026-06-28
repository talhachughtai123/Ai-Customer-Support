<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';

const props = defineProps({
    conversations: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusCounts: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    activeConversation: { type: Object, default: null },
});

const page = usePage();
const isViewer = computed(
    () => (page.props.auth?.roles ?? []).includes('Viewer'),
);

const search = ref(props.filters.search ?? '');
let searchTimer = null;

watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters({ search: value || undefined }), 300);
});

function applyFilters(overrides = {}) {
    const query = {
        status: props.filters.status || undefined,
        search: search.value || undefined,
        ...overrides,
    };

    router.get(route('conversations.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function filterByStatus(value) {
    const next = props.filters.status === value ? undefined : value;
    applyFilters({ status: next });
}

function openConversation(id) {
    router.get(
        route('conversations.show', id),
        {},
        { preserveState: true, preserveScroll: true },
    );
}

const totalCount = computed(() =>
    Object.values(props.statusCounts).reduce((sum, n) => sum + n, 0),
);

const statusBadge = {
    open: 'bg-emerald-100 text-emerald-700',
    waiting: 'bg-amber-100 text-amber-700',
    assigned: 'bg-blue-100 text-blue-700',
    closed: 'bg-gray-100 text-gray-600',
    spam: 'bg-rose-100 text-rose-700',
};

function formatTime(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    const now = new Date();
    const sameDay = date.toDateString() === now.toDateString();
    return sameDay
        ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function initials(name) {
    return (name || '?')
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

// --- Reply composer ---
const replyForm = useForm({ body: '' });
const messagesEnd = ref(null);

function scrollToBottom() {
    nextTick(() => messagesEnd.value?.scrollIntoView({ behavior: 'smooth' }));
}

watch(() => props.activeConversation?.messages?.length, scrollToBottom);

function sendReply() {
    if (!props.activeConversation || !replyForm.body.trim()) return;

    replyForm.post(
        route('conversations.messages.store', props.activeConversation.id),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => replyForm.reset('body'),
        },
    );
}

function changeStatus(event) {
    router.patch(
        route('conversations.update', props.activeConversation.id),
        { status: event.target.value },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head title="Conversations" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Conversations
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="flex h-[calc(100vh-12rem)] overflow-hidden rounded-lg bg-white shadow-sm"
                >
                    <!-- List pane -->
                    <aside
                        class="flex w-full max-w-sm shrink-0 flex-col border-r border-gray-200"
                    >
                        <div class="border-b border-gray-200 p-3">
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search by subject or customer…"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <button
                                    v-for="s in statuses"
                                    :key="s.value"
                                    type="button"
                                    @click="filterByStatus(s.value)"
                                    class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                                    :class="
                                        filters.status === s.value
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    "
                                >
                                    {{ s.label }}
                                    <span class="opacity-70"
                                        >({{ statusCounts[s.value] ?? 0 }})</span
                                    >
                                </button>
                            </div>
                        </div>

                        <ul class="flex-1 divide-y divide-gray-100 overflow-y-auto">
                            <li
                                v-for="c in conversations.data"
                                :key="c.id"
                                @click="openConversation(c.id)"
                                class="cursor-pointer px-4 py-3 transition hover:bg-gray-50"
                                :class="
                                    activeConversation?.id === c.id
                                        ? 'bg-indigo-50'
                                        : ''
                                "
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600"
                                    >
                                        {{ initials(c.customer.name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-medium text-gray-900">
                                                {{ c.customer.name }}
                                            </p>
                                            <span class="shrink-0 text-xs text-gray-400">
                                                {{ formatTime(c.last_message_at) }}
                                            </span>
                                        </div>
                                        <p class="truncate text-xs text-gray-500">
                                            {{ c.subject || 'No subject' }}
                                        </p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span
                                                class="rounded px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide"
                                                :class="statusBadge[c.status]"
                                            >
                                                {{ c.status }}
                                            </span>
                                            <span
                                                v-if="c.unread_count > 0"
                                                class="rounded-full bg-indigo-600 px-1.5 py-0.5 text-[10px] font-semibold text-white"
                                            >
                                                {{ c.unread_count }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li
                                v-if="conversations.data.length === 0"
                                class="p-6 text-center text-sm text-gray-400"
                            >
                                No conversations match these filters.
                            </li>
                        </ul>
                    </aside>

                    <!-- Window pane -->
                    <section class="flex flex-1 flex-col">
                        <template v-if="activeConversation">
                            <header
                                class="flex items-center justify-between border-b border-gray-200 px-5 py-3"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ activeConversation.customer.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ activeConversation.customer.email }}
                                        · {{ activeConversation.channel }}
                                    </p>
                                </div>
                                <select
                                    :value="activeConversation.status"
                                    @change="changeStatus"
                                    :disabled="isViewer"
                                    class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    <option
                                        v-for="s in statuses"
                                        :key="s.value"
                                        :value="s.value"
                                    >
                                        {{ s.label }}
                                    </option>
                                </select>
                            </header>

                            <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50 px-5 py-4">
                                <div
                                    v-for="m in activeConversation.messages"
                                    :key="m.id"
                                    class="flex"
                                    :class="
                                        m.sender_type === 'customer'
                                            ? 'justify-start'
                                            : 'justify-end'
                                    "
                                >
                                    <div
                                        v-if="m.sender_type === 'system'"
                                        class="mx-auto rounded-full bg-gray-200 px-3 py-1 text-xs text-gray-500"
                                    >
                                        {{ m.body }}
                                    </div>
                                    <div
                                        v-else
                                        class="max-w-md rounded-2xl px-4 py-2 text-sm shadow-sm"
                                        :class="
                                            m.sender_type === 'customer'
                                                ? 'rounded-bl-sm bg-white text-gray-800'
                                                : 'rounded-br-sm bg-indigo-600 text-white'
                                        "
                                    >
                                        <p
                                            v-if="m.sender_type === 'agent'"
                                            class="mb-0.5 text-[10px] font-medium opacity-80"
                                        >
                                            {{ m.sender_name }}
                                        </p>
                                        <p class="whitespace-pre-wrap">{{ m.body }}</p>
                                        <p
                                            class="mt-1 text-right text-[10px]"
                                            :class="
                                                m.sender_type === 'customer'
                                                    ? 'text-gray-400'
                                                    : 'text-indigo-200'
                                            "
                                        >
                                            {{ formatTime(m.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div ref="messagesEnd"></div>
                            </div>

                            <form
                                @submit.prevent="sendReply"
                                class="border-t border-gray-200 p-3"
                            >
                                <div
                                    v-if="isViewer"
                                    class="rounded-md bg-gray-50 px-3 py-2 text-center text-xs text-gray-400"
                                >
                                    Viewers have read-only access.
                                </div>
                                <div v-else class="flex items-end gap-2">
                                    <textarea
                                        v-model="replyForm.body"
                                        rows="2"
                                        placeholder="Type your reply…"
                                        @keydown.enter.exact.prevent="sendReply"
                                        class="flex-1 resize-none rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    ></textarea>
                                    <button
                                        type="submit"
                                        :disabled="replyForm.processing || !replyForm.body.trim()"
                                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        Send
                                    </button>
                                </div>
                            </form>
                        </template>

                        <div
                            v-else
                            class="flex flex-1 items-center justify-center text-sm text-gray-400"
                        >
                            Select a conversation to view the thread.
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
