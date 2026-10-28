<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Update Password
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <label for="current_password" class="block font-medium text-sm text-gray-700">Current Password</label>

                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#6B0B23] focus:ring-[#6B0B23]"
                    autocomplete="current-password"
                />

                <p v-if="form.errors.current_password" class="mt-2 text-sm text-red-600">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div>
                <label for="password" class="block font-medium text-sm text-gray-700">New Password</label>

                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#6B0B23] focus:ring-[#6B0B23]"
                    autocomplete="new-password"
                />

                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block font-medium text-sm text-gray-700">Confirm Password</label>

                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#6B0B23] focus:ring-[#6B0B23]"
                    autocomplete="new-password"
                />

                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-md border border-transparent bg-[#6B0B23] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-[#58091c] focus:bg-[#58091c] focus:outline-none focus:ring-2 focus:ring-[#6B0B23] focus:ring-offset-2 active:bg-[#450716] disabled:opacity-50"
                >
                    Save
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
