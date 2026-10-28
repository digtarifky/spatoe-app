<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Delete Account
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Once your account is deleted, all of its resources and data will be permanently deleted.
            </p>
        </header>

        <button
            type="button"
            @click="confirmUserDeletion"
            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700"
        >
            Delete Account
        </button>

        <!-- Modal Dialog -->
        <div
            v-if="confirmingUserDeletion"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-medium text-gray-900">
                    Are you sure you want to delete your account?
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    Please enter your password to confirm you would like to permanently delete your account.
                </p>

                <div class="mt-4">
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />

                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-red-500 disabled:opacity-50"
                    >
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
