<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';
defineProps<{ items: NavItem[] }>();
const { isCurrentUrl } = useCurrentUrl();
</script>
<template>
    <SidebarGroup class="px-1 py-0">
        <SidebarGroupLabel
            class="mb-2 px-3 text-[11px] font-bold tracking-wide text-sidebar-foreground/55"
        >
            مدیریت فروشگاه
        </SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    class="mb-1 h-11 rounded-xl px-3 text-[13px] font-medium transition-all"
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href"
                        ><component :is="item.icon" /><span>{{
                            item.title
                        }}</span></Link
                    >
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
