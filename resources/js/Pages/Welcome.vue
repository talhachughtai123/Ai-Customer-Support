<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    appName: {
        type: String,
        default: 'AI Customer Support',
    },
    laravelVersion: {
        type: String,
        default: '',
    },
    phpVersion: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Head title="Welcome" />

    <main class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-6 text-center">
        <div class="max-w-xl">
            <p class="mb-2 text-sm font-medium uppercase tracking-widest text-indigo-600">
                MVP 0 — Foundation
            </p>
            <h1 class="text-4xl font-semibold text-gray-900">
                {{ appName }}
            </h1>
            <p class="mt-4 text-lg text-gray-600">
                Laravel + Vue 3 + Inertia + Tailwind are wired up and rendering. The
                foundation is ready for MVP 1 (auth, RBAC, and the dashboard shell).
            </p>
            <p class="mt-8 text-sm text-gray-400">
                Laravel {{ laravelVersion }} · PHP {{ phpVersion }}
            </p>
        </div>
    </main>
</template>
