<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <label for="name" class="block font-medium text-sm text-gray-700">Name</label>

                <input
                    id="name"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#6B0B23] focus:ring-[#6B0B23]"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block font-medium text-sm text-gray-700">Email</label>

                <input
                    id="email"
                    type="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#6B0B23] focus:ring-[#6B0B23]"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6B0B23] focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
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
