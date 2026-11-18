<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { FolderTree, LayoutDashboard, Package, Settings } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem, User } from '@/types';

const page = usePage<{ auth: { user: User | null } }>();
const isAdmin = computed(() => Boolean(page.props.auth.user?.is_admin));
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'داشبورد', href: dashboard(), icon: LayoutDashboard },
    ];

    if (page.props.auth.user?.is_admin) {
        items.push(
            {
                title: 'دسته‌بندی‌ها',
                href: '/admin/categories',
                icon: FolderTree,
            },
            { title: 'محصولات', href: '/admin/products', icon: Package },
            {
                title: 'تنظیمات فروشگاه',
                href: '/admin/settings',
                icon: Settings,
            },
        );
    }

    return items;
});
</script>

<template>
    <Sidebar
        side="right"
        collapsible="icon"
        variant="inset"
        dir="rtl"
        :data-admin-sidebar="isAdmin ? '' : undefined"
    >
        <SidebarHeader class="px-3 pb-4 pt-5">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
