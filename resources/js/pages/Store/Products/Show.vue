<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
};

type ProductImage = {
    id: number;
    url: string;
    alt: string | null;
};

type ProductVariant = {
    id: number;
    name: string;
    options: Record<string, string> | null;
    is_available: boolean;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    categories: Category[];
    images: ProductImage[];
    variants: ProductVariant[];
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
                v-if="product.images.length"
                class="mb-8 grid gap-3 sm:grid-cols-2"
            >
                <img
                    v-for="image in product.images"
                    :key="image.id"
                    :src="image.url"
                    :alt="image.alt ?? product.name"
                    class="aspect-[4/3] w-full rounded-xl bg-muted object-cover"
                />
            </div>
            <div
                v-else
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

            <div v-if="product.variants.length" class="mt-8">
                <h2 class="mb-3 text-sm font-semibold">گونه‌های محصول</h2>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="variant in product.variants"
                        :key="variant.id"
                        class="rounded-lg border px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium">{{
                                variant.name
                            }}</span>
                            <span
                                class="text-xs"
                                :class="
                                    variant.is_available
                                        ? 'text-green-600'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ variant.is_available ? 'موجود' : 'ناموجود' }}
                            </span>
                        </div>
                        <p
                            v-if="
                                variant.options &&
                                Object.keys(variant.options).length
                            "
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{
                                Object.entries(variant.options)
                                    .map(([key, value]) => key + ': ' + value)
                                    .join('، ')
                            }}
                        </p>
                    </div>
                </div>
            </div>

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
