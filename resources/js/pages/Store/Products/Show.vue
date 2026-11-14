<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    categories: Category[];
};

const props = defineProps<{ slug: string }>();
const product = ref<Product | null>(null);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    try {
        const response = await fetch(
            '/api/v1/products/' + encodeURIComponent(props.slug),
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (!response.ok) {
            throw new Error('محصول موردنظر پیدا نشد.');
        }

        const payload = (await response.json()) as { data: Product };
        product.value = payload.data;
    } catch {
        errorMessage.value = 'محصول موردنظر پیدا نشد یا در دسترس نیست.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head :title="product?.name ?? 'محصول'" />

    <nav class="mb-6 text-sm text-muted-foreground">
        <Link href="/" class="hover:text-foreground">فروشگاه</Link>
        <span class="mx-2">/</span>
        <span>{{ product?.name ?? 'محصول' }}</span>
    </nav>

    <p v-if="loading" class="text-sm text-muted-foreground">
        در حال دریافت اطلاعات محصول...
    </p>

    <div
        v-else-if="errorMessage"
        role="alert"
        class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
    >
        {{ errorMessage }}
    </div>

    <article v-else-if="product" class="mx-auto max-w-3xl">
        <div class="rounded-2xl border p-6 sm:p-10">
            <div
                class="mb-8 flex aspect-[16/9] items-center justify-center rounded-xl bg-muted text-sm text-muted-foreground"
            >
                تصویر محصول
            </div>

            <h1 class="text-3xl font-bold">{{ product.name }}</h1>

            <p class="mt-5 leading-8 whitespace-pre-line text-muted-foreground">
                {{
                    product.description ||
                    'توضیحاتی برای این محصول ثبت نشده است.'
                }}
            </p>

            <div v-if="product.categories.length" class="mt-8">
                <h2 class="mb-3 text-sm font-semibold">دسته‌بندی‌ها</h2>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="category in product.categories"
                        :key="category.id"
                        :href="'/categories/' + category.slug"
                        class="rounded-full bg-muted px-3 py-1.5 text-sm hover:bg-muted/70"
                    >
                        {{ category.name }}
                    </Link>
                </div>
            </div>
        </div>
    </article>
</template>
