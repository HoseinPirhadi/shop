<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage<{ auth: { user: { name: string } | null } }>();
const isAuthenticated = computed(() => page.props.auth.user !== null);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8"
            >
                <Link href="/" class="text-xl font-bold tracking-tight">
                    فروشگاه
                </Link>

                <nav class="flex items-center gap-3 text-sm sm:gap-5">
                    <Link
                        href="/"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        خانه
                    </Link>
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground"
                    >
                        حساب کاربری
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="text-muted-foreground hover:text-foreground"
                        >
                            ورود
                        </Link>
                        <Link
                            href="/register"
                            class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground"
                        >
                            ثبت‌نام
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>

        <footer class="border-t">
            <div
                class="mx-auto max-w-7xl px-4 py-5 text-sm text-muted-foreground sm:px-6 lg:px-8"
            >
                فروشگاه اینترنتی
            </div>
        </footer>
    </div>
</template>
