<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';
const sidebarNavItems: NavItem[] = [
    { title: 'اطلاعات حساب', href: editProfile() },
    { title: 'امنیت حساب', href: editSecurity() },
    { title: 'نمایش و پوسته', href: editAppearance() },
    { title: 'نشانی‌ها', href: '/settings/addresses' },
];
const { isCurrentOrParentUrl } = useCurrentUrl();
</script>
<template>
    <div class="px-4 py-6 md:px-7">
        <Heading
            title="تنظیمات حساب"
            description="مدیریت اطلاعات شخصی و تنظیمات حساب کاربری"
        />
        <div class="flex flex-col gap-8 lg:flex-row">
            <aside class="w-full max-w-xl lg:w-52">
                <nav class="flex flex-col gap-1" aria-label="تنظیمات حساب">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                        ><Link :href="item.href">{{ item.title }}</Link></Button
                    >
                </nav>
            </aside>
            <Separator class="my-2 lg:hidden" />
            <div class="min-w-0 flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12"><slot /></section>
            </div>
        </div>
    </div>
</template>
