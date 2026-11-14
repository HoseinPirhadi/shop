<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
const name = usePage().props.name;
</script>
<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"><AppLogoIcon class="size-5 fill-current text-white dark:text-black" /></div>
    <div class="me-1 grid min-w-0 flex-1 text-right text-sm">
        <span class="truncate font-semibold leading-tight">{{ name }}</span>
        <span class="mt-0.5 text-xs text-sidebar-foreground/60">پنل مدیریت فروشگاه</span>
    </div>
</template>
