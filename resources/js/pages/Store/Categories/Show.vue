<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
};

const props = defineProps<{ slug: string }>();
const category = ref<Category | null>(null);
const products = ref<Product[]>([]);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    try {
        const [categoryResponse, productResponse] = await Promise.all([
            fetch('/api/v1/categories/' + encodeURIComponent(props.slug), {
                headers: { Accept: 'application/json' },
            }),
            fetch(
                '/api/v1/products?category=' + encodeURIComponent(props.slug),
                {
                    headers: { Accept: 'application/json' },
                },
            ),
        ]);

        if (!categoryResponse.ok) {
            throw new Error('دسته‌بندی موردنظر پیدا نشد.');
        }

        const categoryPayload = (await categoryResponse.json()) as {
            data: Category;
        };
        category.value = categoryPayload.data;

        if (productResponse.ok) {
            const productPayload = (await productResponse.json()) as {
                data: Product[];
            };
            products.value = productPayload.data;
        }
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

    <div
        v-if="errorMessage"
        role="alert"
        class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
    >
        {{ errorMessage }}
    </div>

    <template v-else>
        <header class="mb-8">
            <h1 class="text-3xl font-bold">{{ category?.name }}</h1>
            <p
                v-if="category?.description"
                class="mt-3 max-w-3xl leading-7 text-muted-foreground"
            >
                {{ category.description }}
            </p>
        </header>

        <p v-if="loading" class="text-sm text-muted-foreground">
            در حال دریافت محصولات...
        </p>
        <p
            v-else-if="products.length === 0"
            class="rounded-lg border p-6 text-sm text-muted-foreground"
        >
            هنوز محصول فعالی در این دسته‌بندی وجود ندارد.
        </p>
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="product in products"
                :key="product.id"
                class="flex flex-col rounded-xl border p-5"
            >
                <h2 class="font-semibold">{{ product.name }}</h2>
                <p class="mt-2 flex-1 text-sm leading-6 text-muted-foreground">
                    {{
                        product.description ||
                        'توضیحاتی برای این محصول ثبت نشده است.'
                    }}
                </p>
                <Link
                    :href="'/products/' + product.slug"
                    class="mt-5 text-sm font-medium text-primary hover:underline"
                >
                    مشاهده جزئیات
                </Link>
            </article>
        </div>
    </template>
</template>
