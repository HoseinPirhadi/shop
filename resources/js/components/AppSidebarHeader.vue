<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';
withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), { breadcrumbs: () => [] });
</script>
<template>
    <header class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sidebar-border/70 px-5 md:px-7">
        <div class="flex min-w-0 items-center gap-3"><SidebarTrigger class="me-1" /><Breadcrumbs v-if="breadcrumbs?.length" :breadcrumbs="breadcrumbs" /></div>
        <span class="hidden text-xs font-medium text-muted-foreground sm:block">سامانه مدیریت فروشگاه</span>
    </header>
</template>
