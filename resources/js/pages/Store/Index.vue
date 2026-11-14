<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    categories: Category[];
};

const categories = ref<Category[]>([]);
const products = ref<Product[]>([]);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    try {
        const [categoryResponse, productResponse] = await Promise.all([
            fetch('/api/v1/categories', {
                headers: { Accept: 'application/json' },
            }),
            fetch('/api/v1/products', {
                headers: { Accept: 'application/json' },
            }),
        ]);

        if (!categoryResponse.ok || !productResponse.ok) {
            throw new Error('دریافت اطلاعات فروشگاه ناموفق بود.');
        }

        const categoryPayload = (await categoryResponse.json()) as {
            data: Category[];
        };
        const productPayload = (await productResponse.json()) as {
            data: Product[];
        };

        categories.value = categoryPayload.data.filter(
            (category) => category.parent_id === null,
        );
        products.value = productPayload.data;
    } catch {
        errorMessage.value = 'در حال حاضر دریافت اطلاعات فروشگاه ممکن نیست.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="فروشگاه" />

    <section
        class="mb-10 rounded-2xl bg-muted/50 px-6 py-10 sm:px-10 sm:py-14"
    >
        <p class="mb-3 text-sm font-medium text-primary">به فروشگاه خوش آمدید</p>
        <h1 class="max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">
            محصولات موردنظرتان را پیدا کنید
        </h1>
        <p class="mt-4 max-w-2xl leading-7 text-muted-foreground">
            دسته‌بندی‌ها را مرور کنید و محصولات موجود در فروشگاه را ببینید.
        </p>
    </section>

    <div
        v-if="errorMessage"
        role="alert"
        class="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
    >
        {{ errorMessage }}
    </div>

    <section class="mb-12">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">دسته‌بندی‌ها</h2>
        </div>

        <p v-if="loading" class="text-sm text-muted-foreground">
            در حال دریافت دسته‌بندی‌ها...
        </p>
        <p
            v-else-if="categories.length === 0"
            class="rounded-lg border p-6 text-sm text-muted-foreground"
        >
            هنوز دسته‌بندی فعالی ثبت نشده است.
        </p>
        <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="'/categories/' + category.slug"
                class="rounded-xl border p-5 transition hover:border-primary hover:bg-muted/40"
            >
                <span class="font-medium">{{ category.name }}</span>
                <span class="mt-2 block text-sm text-muted-foreground">
                    مشاهده محصولات
                </span>
            </Link>
        </div>
    </section>

    <section>
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">محصولات</h2>
        </div>

        <p v-if="loading" class="text-sm text-muted-foreground">
            در حال دریافت محصولات...
        </p>
        <p
            v-else-if="products.length === 0"
            class="rounded-lg border p-6 text-sm text-muted-foreground"
        >
            هنوز محصول فعالی برای نمایش وجود ندارد.
        </p>
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="product in products"
                :key="product.id"
                class="flex flex-col rounded-xl border p-5"
            >
                <h3 class="font-semibold">{{ product.name }}</h3>
                <p class="mt-2 line-clamp-3 flex-1 text-sm leading-6 text-muted-foreground">
                    {{ product.description || 'توضیحاتی برای این محصول ثبت نشده است.' }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="category in product.categories"
                        :key="category.id"
                        :href="'/categories/' + category.slug"
                        class="rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground"
                    >
                        {{ category.name }}
                    </Link>
                </div>
                <Link
                    :href="'/products/' + product.slug"
                    class="mt-5 text-sm font-medium text-primary hover:underline"
                >
                    مشاهده جزئیات
                </Link>
            </article>
        </div>
    </section>
</template>
