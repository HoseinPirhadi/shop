<script setup lang="ts">
import ProductListing from '@/components/store/ProductListing.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
};

const props = defineProps<{ slug: string }>();
const category = ref<Category | null>(null);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    try {
        const response = await fetch(
            '/api/v1/categories/' + encodeURIComponent(props.slug),
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            throw new Error('دسته‌بندی موردنظر پیدا نشد.');
        }

        const payload = (await response.json()) as { data: Category };
        category.value = payload.data;
    } catch {
        errorMessage.value = 'دسته‌بندی موردنظر پیدا نشد یا در دسترس نیست.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head :title="category?.name ?? 'دسته‌بندی'" />

    <nav class="mb-6 text-sm text-muted-foreground">
        <Link href="/" class="hover:text-foreground">فروشگاه</Link>
        <span class="mx-2">/</span>
        <span>{{ category?.name ?? 'دسته‌بندی' }}</span>
    </nav>

    <p v-if="loading" class="text-sm text-muted-foreground">
        در حال دریافت دسته‌بندی...
    </p>

    <div
        v-else-if="errorMessage"
        role="alert"
        class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
    >
        {{ errorMessage }}
    </div>

    <template v-else-if="category">
        <header class="mb-8">
            <h1 class="text-3xl font-bold">{{ category.name }}</h1>
            <p
                v-if="category.description"
                class="mt-3 max-w-3xl leading-7 text-muted-foreground"
            >
                {{ category.description }}
            </p>
        </header>

        <ProductListing
            :key="category.slug"
            :category-slug="category.slug"
            :heading="'محصولات ' + category.name"
        />
    </template>
</template>
