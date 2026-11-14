<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

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

type ProductPagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = withDefaults(
    defineProps<{
        categorySlug?: string;
        heading?: string;
    }>(),
    {
        categorySlug: '',
        heading: 'محصولات',
    },
);

const products = ref<Product[]>([]);
const pagination = ref<ProductPagination | null>(null);
const searchInput = ref('');
const search = ref('');
const sort = ref('featured');
const page = ref(1);
const loading = ref(true);
const errorMessage = ref('');
let activeRequest: AbortController | null = null;

const hasProducts = computed(() => products.value.length > 0);

async function loadProducts() {
    activeRequest?.abort();
    const controller = new AbortController();
    activeRequest = controller;
    loading.value = true;
    errorMessage.value = '';

    const params = new URLSearchParams({
        page: String(page.value),
        per_page: '12',
        sort: sort.value,
    });

    if (props.categorySlug) params.set('category', props.categorySlug);
    if (search.value) params.set('q', search.value);

    try {
        const response = await fetch('/api/v1/products?' + params.toString(), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error('دریافت محصولات ناموفق بود.');
        }

        const payload = (await response.json()) as {
            data: Product[];
            meta: ProductPagination;
        };

        products.value = payload.data;
        pagination.value = payload.meta;
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }
        if (activeRequest !== controller) return;
        products.value = [];
        pagination.value = null;
        errorMessage.value = 'در حال حاضر دریافت محصولات ممکن نیست.';
    } finally {
        if (activeRequest === controller) loading.value = false;
    }
}

function submitSearch() {
    search.value = searchInput.value.trim();
    page.value = 1;
    void loadProducts();
}

function changeSort(value: string) {
    sort.value = value;
    page.value = 1;
    void loadProducts();
}

function changePage(nextPage: number) {
    if (
        loading.value ||
        !pagination.value ||
        nextPage < 1 ||
        nextPage > pagination.value.last_page
    ) {
        return;
    }

    page.value = nextPage;
    void loadProducts();
}

watch(
    () => props.categorySlug,
    () => {
        page.value = 1;
        void loadProducts();
    },
);

onMounted(() => {
    void loadProducts();
});

onBeforeUnmount(() => {
    activeRequest?.abort();
});
</script>

<template>
    <section>
        <div
            class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h2 class="text-xl font-semibold">{{ heading }}</h2>
                <p
                    v-if="pagination"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ pagination.total.toLocaleString('fa-IR') }} محصول
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <form
                    class="flex gap-2"
                    role="search"
                    @submit.prevent="submitSearch"
                >
                    <label
                        class="sr-only"
                        for="store-product-search"
                    >
                        جستجوی محصولات
                    </label>
                    <input
                        id="store-product-search"
                        v-model="searchInput"
                        type="search"
                        maxlength="120"
                        placeholder="جستجوی محصول..."
                        class="h-10 min-w-0 flex-1 rounded-md border bg-background px-3 text-sm sm:w-56"
                    />
                    <button
                        type="submit"
                        class="rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground"
                    >
                        جستجو
                    </button>
                </form>

                <label
                    class="sr-only"
                    for="store-product-sort"
                >
                    مرتب‌سازی محصولات
                </label>
                <select
                    id="store-product-sort"
                    :value="sort"
                    class="h-10 rounded-md border bg-background px-3 text-sm"
                    @change="changeSort(($event.target as HTMLSelectElement).value)"
                >
                    <option value="featured">پیش‌فرض</option>
                    <option value="newest">جدیدترین</option>
                    <option value="name_asc">نام: الف تا ی</option>
                    <option value="name_desc">نام: ی تا الف</option>
                </select>
            </div>
        </div>

        <div
            v-if="errorMessage"
            role="alert"
            class="mb-6 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
        >
            {{ errorMessage }}
        </div>

        <p
            v-if="loading"
            class="rounded-lg border p-6 text-sm text-muted-foreground"
        >
            در حال دریافت محصولات...
        </p>
        <p
            v-else-if="!hasProducts"
            class="rounded-lg border p-6 text-sm text-muted-foreground"
        >
            {{
                search
                    ? 'محصولی با این عبارت پیدا نشد.'
                    : 'هنوز محصول فعالی برای نمایش وجود ندارد.'
            }}
        </p>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="product in products"
                :key="product.id"
                class="flex flex-col rounded-xl border p-5 transition hover:border-primary/50"
            >
                <h3 class="font-semibold">{{ product.name }}</h3>
                <p
                    class="mt-2 line-clamp-3 flex-1 text-sm leading-6 text-muted-foreground"
                >
                    {{
                        product.description ||
                        'توضیحاتی برای این محصول ثبت نشده است.'
                    }}
                </p>
                <div
                    v-if="product.categories.length"
                    class="mt-4 flex flex-wrap gap-2"
                >
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

        <nav
            v-if="pagination && pagination.last_page > 1"
            aria-label="صفحه‌بندی محصولات"
            class="mt-8 flex items-center justify-between gap-4 border-t pt-5"
        >
            <button
                type="button"
                :disabled="page <= 1 || loading"
                class="rounded-md border px-4 py-2 text-sm disabled:opacity-40"
                @click="changePage(page - 1)"
            >
                قبلی
            </button>
            <span class="text-sm text-muted-foreground">
                صفحه {{ pagination.current_page.toLocaleString('fa-IR') }}
                از {{ pagination.last_page.toLocaleString('fa-IR') }}
            </span>
            <button
                type="button"
                :disabled="page >= pagination.last_page || loading"
                class="rounded-md border px-4 py-2 text-sm disabled:opacity-40"
                @click="changePage(page + 1)"
            >
                بعدی
            </button>
        </nav>
    </section>
</template>
