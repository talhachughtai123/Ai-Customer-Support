<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const submit = () => {
    form.post(route('two-factor.login.store'), {
        onFinish: () => form.reset('code', 'recovery_code'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Two-factor confirmation" />

        <div class="mb-4 text-sm text-gray-600">
            <template v-if="!recovery">
                Enter the authentication code from your authenticator app.
            </template>
            <template v-else>
                Enter one of your emergency recovery codes.
            </template>
        </div>

        <form @submit.prevent="submit">
            <div v-if="!recovery">
                <InputLabel for="code" value="Code" />
                <TextInput
                    id="code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    v-model="form.code"
                    autofocus
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div v-else>
                <InputLabel for="recovery_code" value="Recovery code" />
                <TextInput
                    id="recovery_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.recovery_code"
                    autocomplete="one-time-code"
                />
                <InputError class="mt-2" :message="form.errors.recovery_code" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <button
                    type="button"
                    class="text-sm text-gray-600 underline hover:text-gray-900"
                    @click="recovery = !recovery"
                >
                    {{ recovery ? 'Use authentication code' : 'Use a recovery code' }}
                </button>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
