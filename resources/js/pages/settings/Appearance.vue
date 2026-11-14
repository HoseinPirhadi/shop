<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';
defineOptions({ layout: { breadcrumbs: [{ title: 'نمایش و پوسته', href: edit() }] } });
</script>
<template>
    <Head title="نمایش و پوسته" />
    <h1 class="sr-only">نمایش و پوسته</h1>
    <div class="space-y-6"><Heading variant="small" title="نمایش و پوسته" description="انتخاب حالت روشن، تیره یا هماهنگ با دستگاه" /><AppearanceTabs /></div>
</template>
