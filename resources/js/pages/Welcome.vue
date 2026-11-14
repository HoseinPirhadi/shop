<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
</script>
<template>
    <Head title="فروشگاه اینترنتی" />
    <div class="min-h-screen bg-[#f7f8fc] text-slate-800 dark:bg-slate-950 dark:text-slate-100">
        <header class="border-b border-slate-200 bg-white/90 dark:border-slate-800 dark:bg-slate-900/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5">
                <Link href="/" class="text-xl font-black">فروشگاه</Link>
                <nav class="flex items-center gap-3 text-sm">
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="rounded-xl bg-indigo-600 px-5 py-2.5 font-semibold text-white">حساب کاربری</Link>
                    <template v-else>
                        <Link :href="login()" class="rounded-xl px-4 py-2 font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">ورود</Link>
                        <Link :href="register()" class="rounded-xl bg-indigo-600 px-5 py-2.5 font-semibold text-white">ثبت‌نام</Link>
                    </template>
                </nav>
            </div>
        </header>
        <main class="mx-auto grid min-h-[75vh] max-w-7xl items-center gap-12 px-5 py-16 lg:grid-cols-2">
            <section>
                <span class="inline-flex rounded-full bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">تجربه‌ای ساده و مطمئن از خرید آنلاین</span>
                <h1 class="mt-6 text-4xl leading-[1.5] font-black tracking-tight md:text-5xl">آنچه نیاز دارید،<br /><span class="text-indigo-600 dark:text-indigo-400">در یک فروشگاه</span></h1>
                <p class="mt-6 max-w-xl text-base leading-8 text-slate-500 dark:text-slate-400">محصولات را مرور کنید، دسته‌بندی‌ها را ببینید و برای خریدی آسان آماده شوید. فروشگاه به‌تدریج با امکانات تازه تکمیل می‌شود.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link href="/categories" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-lg shadow-indigo-600/20">مشاهده محصولات</Link>
                    <Link v-if="!$page.props.auth.user" :href="register()" class="rounded-xl border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">ساخت حساب کاربری</Link>
                </div>
            </section>
            <section class="relative mx-auto w-full max-w-xl">
                <div class="absolute -inset-5 rounded-[2.5rem] bg-gradient-to-br from-indigo-200/60 via-sky-100/50 to-violet-200/60 blur-2xl dark:from-indigo-900/30 dark:via-sky-900/20 dark:to-violet-900/30"></div>
                <div class="relative rounded-[2rem] border border-white/80 bg-white p-8 shadow-2xl shadow-slate-300/30 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex aspect-square flex-col justify-between rounded-2xl bg-indigo-50 p-5 dark:bg-indigo-500/10"><span class="text-3xl">✦</span><strong class="text-lg">انتخاب آسان</strong><span class="text-sm text-slate-500 dark:text-slate-400">مرور دسته‌بندی‌ها</span></div>
                        <div class="mt-8 flex aspect-square flex-col justify-between rounded-2xl bg-sky-50 p-5 dark:bg-sky-500/10"><span class="text-3xl">◈</span><strong class="text-lg">اطلاعات روشن</strong><span class="text-sm text-slate-500 dark:text-slate-400">جزئیات محصولات</span></div>
                        <div class="-mt-8 flex aspect-square flex-col justify-between rounded-2xl bg-violet-50 p-5 dark:bg-violet-500/10"><span class="text-3xl">⌁</span><strong class="text-lg">حساب شخصی</strong><span class="text-sm text-slate-500 dark:text-slate-400">مدیریت اطلاعات</span></div>
                        <div class="flex aspect-square flex-col justify-between rounded-2xl bg-emerald-50 p-5 dark:bg-emerald-500/10"><span class="text-3xl">✓</span><strong class="text-lg">تجربه مطمئن</strong><span class="text-sm text-slate-500 dark:text-slate-400">طراحی واکنش‌گرا</span></div>
                    </div>
                </div>
            </section>
        </main>
        <footer class="border-t border-slate-200 py-6 text-center text-sm text-slate-400 dark:border-slate-800">تمام حقوق برای فروشگاه محفوظ است.</footer>
    </div>
</template>