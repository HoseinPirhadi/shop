<script setup lang="ts">
import ProductListing from '@/components/store/ProductListing.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
};

const categories = ref<Category[]>([]);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    try {
        const response = await fetch('/api/v1/categories', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('دریافت دسته‌بندی‌ها ناموفق بود.');
        }

        const payload = (await response.json()) as {
            data: Category[];
        };

        categories.value = payload.data.filter(
            (category) => category.parent_id === null,
        );
    } catch {
        errorMessage.value = 'در حال حاضر دریافت دسته‌بندی‌ها ممکن نیست.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="فروشگاه" />

    <section class="mb-10 rounded-2xl bg-muted/50 px-6 py-10 sm:px-10 sm:py-14">
        <p class="mb-3 text-sm font-medium text-primary">
            به فروشگاه خوش آمدید
        </p>
        <h1 class="max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">
            محصولات موردنظرتان را پیدا کنید
        </h1>
        <p class="mt-4 max-w-2xl leading-7 text-muted-foreground">
            دسته‌بندی‌ها را مرور کنید یا محصول موردنظرتان را جستجو کنید.
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
        <h2 class="mb-5 text-xl font-semibold">دسته‌بندی‌ها</h2>

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

    <ProductListing heading="همه محصولات" />
</template>
