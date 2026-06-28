<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

// The visitor has no account. The conversation token is minted on start and
// kept in localStorage so the chat resumes across reloads.
const STORAGE_KEY = 'widget.token';

const open = ref(false);
const token = ref(localStorage.getItem(STORAGE_KEY));
const started = computed(() => !!token.value);

const messages = ref([]);
const status = ref('open');
const agentTyping = ref(false);
const loading = ref(false);
const sending = ref(false);

const form = ref({ name: '', email: '' });
const draft = ref('');
const scrollEnd = ref(null);

let channel = null;
let lastTypingAt = 0;
let agentTypingTimer = null;

const unreadFromAgent = computed(
    () =>
        !open.value &&
        messages.value.some((m) => m.sender_type !== 'customer' && !m.seen),
);

function scrollToBottom() {
    nextTick(() => scrollEnd.value?.scrollIntoView({ behavior: 'smooth' }));
}

function applyConversation(conv) {
    messages.value = (conv.messages ?? []).map((m) => ({ ...m, seen: true }));
    status.value = conv.status;
    scrollToBottom();
    markRead();
}

function reset() {
    if (token.value) unsubscribe();
    localStorage.removeItem(STORAGE_KEY);
    token.value = null;
    messages.value = [];
}

async function startChat() {
    if (!form.value.name.trim() || loading.value) return;

    loading.value = true;
    try {
        const { data } = await window.axios.post('/widget/conversations', {
            name: form.value.name,
            email: form.value.email || null,
        });
        token.value = data.token;
        localStorage.setItem(STORAGE_KEY, data.token);
        applyConversation(data.conversation);
        subscribe();
    } finally {
        loading.value = false;
    }
}

async function loadThread() {
    loading.value = true;
    try {
        const { data } = await window.axios.get(
            `/widget/conversations/${token.value}`,
        );
        applyConversation(data.conversation);
        subscribe();
    } catch (e) {
        if (e.response?.status === 404) reset();
    } finally {
        loading.value = false;
    }
}

async function send() {
    const body = draft.value.trim();
    if (!body || sending.value) return;

    sending.value = true;
    try {
        const { data } = await window.axios.post(
            `/widget/conversations/${token.value}/messages`,
            { body },
        );
        pushMessage(data.message);
        draft.value = '';
    } finally {
        sending.value = false;
    }
}

function pushMessage(m) {
    if (messages.value.some((x) => x.id === m.id)) return;
    messages.value.push({ ...m, seen: open.value });
    scrollToBottom();
}

function subscribe() {
    if (!window.Echo || !token.value || channel) return;

    channel = window.Echo.channel('widget.' + token.value)
        .listen('.message.sent', (e) => {
            pushMessage(e.message);
            if (e.message.sender_type !== 'customer') markRead();
        })
        .listen('.messages.read', (e) => {
            // The agent read our messages — flip our bubbles to "Read".
            if (e.reader === 'agent') {
                messages.value.forEach((m) => {
                    if (m.sender_type === 'customer' && !m.read_at) {
                        m.read_at = new Date().toISOString();
                    }
                });
            }
        })
        .listen('.participant.typing', (e) => {
            if (e.side === 'agent' || e.side === 'ai') {
                agentTyping.value = true;
                clearTimeout(agentTypingTimer);
                agentTypingTimer = setTimeout(
                    () => (agentTyping.value = false),
                    2500,
                );
            }
        })
        .listen('.conversation.updated', (e) => {
            status.value = e.status;
        });
}

function unsubscribe() {
    if (channel && window.Echo) window.Echo.leave('widget.' + token.value);
    channel = null;
}

// Tell the server the customer has seen the agent's messages (read receipts) —
// only while the panel is actually open.
function markRead() {
    if (!token.value || !open.value) return;
    messages.value.forEach((m) => (m.seen = true));
    window.axios
        .post(`/widget/conversations/${token.value}/read`)
        .catch(() => {});
}

function notifyTyping() {
    const now = Date.now();
    if (!token.value || now - lastTypingAt < 1000) return;
    lastTypingAt = now;
    window.axios
        .post(`/widget/conversations/${token.value}/typing`)
        .catch(() => {});
}

function toggle() {
    open.value = !open.value;
    if (open.value && started.value) markRead();
}

function formatTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

onMounted(() => {
    if (token.value) loadThread();
});

onBeforeUnmount(() => {
    unsubscribe();
    clearTimeout(agentTypingTimer);
});
</script>

<template>
    <Head title="Live Chat" />

    <!-- Demo host page so the floating widget looks like it sits on a real site. -->
    <div
        class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-700 px-6 py-20 text-slate-200"
    >
        <div class="mx-auto max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-300">
                Demo storefront
            </p>
            <h1 class="mt-3 text-4xl font-bold text-white">
                Have a question? We're here to help.
            </h1>
            <p class="mt-4 max-w-lg text-slate-300">
                This page simulates a customer-facing website. Click the chat
                bubble in the corner to start a live conversation — an agent
                replies in real time from the inbox.
            </p>
        </div>

        <!-- Floating widget -->
        <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end">
            <!-- Panel -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-3 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-3 opacity-0"
            >
                <div
                    v-if="open"
                    class="mb-3 flex h-[30rem] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                >
                    <!-- Header -->
                    <div class="bg-indigo-600 px-4 py-3 text-white">
                        <p class="text-sm font-semibold">Support chat</p>
                        <p class="text-xs text-indigo-200">
                            <span v-if="status === 'closed'">
                                This conversation was closed
                            </span>
                            <span v-else>We typically reply in a few minutes</span>
                        </p>
                    </div>

                    <!-- Pre-chat form -->
                    <div
                        v-if="!started"
                        class="flex flex-1 flex-col justify-center gap-3 p-5"
                    >
                        <p class="text-sm text-gray-600">
                            👋 Hi there! Tell us who you are and we'll get
                            started.
                        </p>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Your name"
                            class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keydown.enter="startChat"
                        />
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="Email (optional)"
                            class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keydown.enter="startChat"
                        />
                        <button
                            type="button"
                            :disabled="!form.name.trim() || loading"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                            @click="startChat"
                        >
                            Start chat
                        </button>
                    </div>

                    <!-- Thread -->
                    <template v-else>
                        <div
                            class="flex-1 space-y-2 overflow-y-auto bg-gray-50 px-3 py-3"
                        >
                            <div
                                class="mx-auto max-w-[14rem] rounded-full bg-gray-200 px-3 py-1 text-center text-[11px] text-gray-500"
                            >
                                You're chatting with our support team
                            </div>

                            <div
                                v-for="m in messages"
                                :key="m.id"
                                class="flex"
                                :class="
                                    m.sender_type === 'customer'
                                        ? 'justify-end'
                                        : 'justify-start'
                                "
                            >
                                <div
                                    v-if="m.sender_type === 'system'"
                                    class="mx-auto rounded-full bg-gray-200 px-3 py-1 text-[11px] text-gray-500"
                                >
                                    {{ m.body }}
                                </div>
                                <div
                                    v-else
                                    class="max-w-[15rem] rounded-2xl px-3 py-2 text-sm shadow-sm"
                                    :class="
                                        m.sender_type === 'customer'
                                            ? 'rounded-br-sm bg-indigo-600 text-white'
                                            : 'rounded-bl-sm bg-white text-gray-800'
                                    "
                                >
                                    <p
                                        v-if="m.sender_type === 'ai' || (m.sender_type === 'agent' && m.sender_name)"
                                        class="mb-0.5 text-[10px] font-medium text-indigo-500"
                                    >
                                        {{ m.sender_type === 'ai' ? 'Assistant 🤖' : m.sender_name }}
                                    </p>
                                    <p class="whitespace-pre-wrap">{{ m.body }}</p>
                                    <p
                                        class="mt-1 text-right text-[10px]"
                                        :class="
                                            m.sender_type === 'customer'
                                                ? 'text-indigo-200'
                                                : 'text-gray-400'
                                        "
                                    >
                                        {{ formatTime(m.created_at) }}
                                        <span v-if="m.sender_type === 'customer'">
                                            · {{ m.read_at ? 'Read' : 'Sent' }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="agentTyping"
                                class="text-xs italic text-gray-400"
                            >
                                Support is typing…
                            </div>
                            <div ref="scrollEnd"></div>
                        </div>

                        <!-- Composer -->
                        <form
                            class="flex items-end gap-2 border-t border-gray-200 p-2"
                            @submit.prevent="send"
                        >
                            <textarea
                                v-model="draft"
                                rows="1"
                                placeholder="Type a message…"
                                class="flex-1 resize-none rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @input="notifyTyping"
                                @keydown.enter.exact.prevent="send"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="sending || !draft.trim()"
                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Send
                            </button>
                        </form>
                    </template>
                </div>
            </transition>

            <!-- Launcher bubble -->
            <button
                type="button"
                class="relative flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg transition hover:bg-indigo-700"
                @click="toggle"
            >
                <span
                    v-if="unreadFromAgent"
                    class="absolute -right-0.5 -top-0.5 h-3.5 w-3.5 rounded-full border-2 border-indigo-600 bg-rose-500"
                ></span>
                <svg
                    v-if="!open"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-11.5 7.2L3 21l1.8-6.5A8 8 0 1121 12z"
                    />
                </svg>
                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>
