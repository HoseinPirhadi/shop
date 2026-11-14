<script setup lang="ts">
import ProductImageManager from '@/components/admin/ProductImageManager.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
};

type ProductImage = {
    id: number;
    url: string;
    alt: string | null;
    sort_order: number;
    is_primary: boolean;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    categories: Category[];
    images: ProductImage[];
};

type ProductPage = {
    data: Product[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
};

type ProductForm = {
    name: string;
    slug: string;
    description: string;
    category_ids: number[];
    sort_order: number;
    is_active: boolean;
};

type ApiError = {
    message?: string;
    errors?: Record<string, string[]>;
};

const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const pagination = ref<ProductPage['links'] | null>(null);
const loading = ref(true);
const saving = ref(false);
const deletingId = ref<number | null>(null);
const errorMessage = ref('');
const formVisible = ref(false);
const editingId = ref<number | null>(null);
const fieldErrors = ref<Record<string, string[]>>({});
const formImages = ref<ProductImage[]>([]);

const emptyForm = (): ProductForm => ({
    name: '',
    slug: '',
    description: '',
    category_ids: [],
    sort_order: 0,
    is_active: false,
});

const form = ref<ProductForm>(emptyForm());

async function apiRequest<T>(
    url: string,
    options: RequestInit = {},
): Promise<T> {
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith('XSRF-TOKEN='));
    const token = cookie
        ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length))
        : '';

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': token,
            ...(options.headers as Record<string, string> | undefined),
        },
    });

    if (response.status === 204) {
        return undefined as T;
    }

    const payload = (await response.json()) as T & ApiError;

    if (!response.ok) {
        throw payload;
    }

    return payload;
}

async function loadProducts(url = '/api/v1/admin/products') {
    loading.value = true;
    errorMessage.value = '';

    try {
        const result = await apiRequest<ProductPage>(url);
        products.value = result.data;
        pagination.value = result.links;
    } catch {
        errorMessage.value =
            'دریافت فهرست محصولات ناموفق بود. صفحه را دوباره بارگذاری کنید.';
    } finally {
        loading.value = false;
    }
}

async function loadCategories() {
    try {
        let nextUrl: string | null = '/api/v1/admin/categories';
        const allCategories: Category[] = [];

        while (nextUrl) {
            const result: {
                data: Category[];
                links: { next: string | null };
            } = await apiRequest(nextUrl);

            allCategories.push(...result.data);
            nextUrl = result.links.next;
        }

        categories.value = allCategories;
    } catch {
        errorMessage.value =
            'دریافت دسته‌بندی‌ها ناموفق بود؛ امکان انتخاب دسته‌بندی وجود ندارد.';
    }
}

function openCreateForm() {
    editingId.value = null;
    form.value = emptyForm();
    fieldErrors.value = {};
    formImages.value = [];
    formVisible.value = true;
}

function openEditForm(product: Product) {
    editingId.value = product.id;
    formImages.value = [...product.images];
    form.value = {
        name: product.name,
        slug: product.slug,
        description: product.description ?? '',
        category_ids: product.categories.map((category) => category.id),
        sort_order: product.sort_order,
        is_active: product.is_active,
    };
    fieldErrors.value = {};
    formVisible.value = true;
}

function closeForm() {
    formVisible.value = false;
    fieldErrors.value = {};
    formImages.value = [];
}

function handleImagesUpdated(images: ProductImage[]) {
    formImages.value = images;
    if (editingId.value !== null) {
        products.value = products.value.map((product) =>
            product.id === editingId.value ? { ...product, images } : product,
        );
    }
}

async function saveProduct() {
    saving.value = true;
    fieldErrors.value = {};

    const url = editingId.value
        ? '/api/v1/admin/products/' + editingId.value
        : '/api/v1/admin/products';

    try {
        await apiRequest(url, {
            method: editingId.value ? 'PATCH' : 'POST',
            body: JSON.stringify(form.value),
        });
        closeForm();
        await loadProducts();
    } catch (error) {
        const apiError = error as ApiError;
        fieldErrors.value = apiError.errors ?? {};
        errorMessage.value = apiError.errors
            ? ''
            : (apiError.message ?? 'ذخیره محصول انجام نشد.');
    } finally {
        saving.value = false;
    }
}

async function deleteProduct(product: Product) {
    if (!window.confirm('محصول «' + product.name + '» حذف شود؟')) {
        return;
    }

    deletingId.value = product.id;
    errorMessage.value = '';

    try {
        await apiRequest('/api/v1/admin/products/' + product.id, {
            method: 'DELETE',
        });
        await loadProducts();
    } catch (error) {
        const apiError = error as ApiError;
        errorMessage.value = apiError.message ?? 'حذف محصول انجام نشد.';
    } finally {
        deletingId.value = null;
    }
}

onMounted(async () => {
    await Promise.all([loadProducts(), loadCategories()]);
});
</script>

<template>
    <Head title="مدیریت محصولات" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">مدیریت محصولات</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    ایجاد محصولات و اتصال آن‌ها به دسته‌بندی‌ها
                </p>
            </div>

            <button
                type="button"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                @click="openCreateForm"
            >
                محصول جدید
            </button>
        </div>

        <div
            v-if="errorMessage"
            role="alert"
            class="rounded-md border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
        >
            {{ errorMessage }}
        </div>

        <section
            v-if="formVisible"
            class="rounded-xl border bg-card p-4 md:p-6"
        >
            <div class="mb-5 flex items-center justify-between">
                <h2 class="font-semibold">
                    {{ editingId ? 'ویرایش محصول' : 'ایجاد محصول' }}
                </h2>
                <button
                    type="button"
                    class="text-sm text-muted-foreground hover:text-foreground"
                    @click="closeForm"
                >
                    بستن
                </button>
            </div>

            <form
                class="grid gap-4 md:grid-cols-2"
                @submit.prevent="saveProduct"
            >
                <label class="grid gap-1.5 text-sm">
                    نام محصول
                    <input
                        v-model="form.name"
                        required
                        maxlength="160"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span
                        v-if="fieldErrors.name"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.name[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    شناسه URL (Slug)
                    <input
                        v-model="form.slug"
                        required
                        maxlength="180"
                        pattern="[A-Za-z0-9_-]+"
                        class="h-10 rounded-md border bg-background px-3"
                        dir="ltr"
                    />
                    <span
                        v-if="fieldErrors.slug"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.slug[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    دسته‌بندی‌ها
                    <select
                        v-model="form.category_ids"
                        multiple
                        size="5"
                        class="rounded-md border bg-background px-3 py-2"
                    >
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <span class="text-xs text-muted-foreground">
                        برای انتخاب چند دسته، کلید Ctrl را نگه دارید.
                    </span>
                    <span
                        v-if="fieldErrors.category_ids"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.category_ids[0] }}
                    </span>
                    <span
                        v-if="fieldErrors['category_ids.0']"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors['category_ids.0'][0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    ترتیب نمایش
                    <input
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span
                        v-if="fieldErrors.sort_order"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.sort_order[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm md:col-span-2">
                    توضیحات
                    <textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="10000"
                        class="rounded-md border bg-background px-3 py-2"
                    />
                    <span
                        v-if="fieldErrors.description"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.description[0] }}
                    </span>
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4"
                    />
                    فعال و قابل نمایش در فروشگاه
                </label>

                <div v-if="editingId" class="md:col-span-2">
                    <ProductImageManager
                        :product-id="editingId"
                        :images="formImages"
                        @update:images="handleImagesUpdated"
                    />
                </div>
                <p
                    v-else
                    class="rounded-md bg-muted/50 p-3 text-sm text-muted-foreground md:col-span-2"
                >
                    برای افزودن تصاویر، ابتدا محصول را ذخیره کنید و سپس آن را
                    ویرایش کنید.
                </p>

                <div class="flex justify-end gap-2 md:col-span-2">
                    <button
                        type="button"
                        class="rounded-md border px-4 py-2 text-sm"
                        @click="closeForm"
                    >
                        انصراف
                    </button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        {{ saving ? 'در حال ذخیره...' : 'ذخیره' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border bg-card">
            <div
                v-if="loading"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                در حال دریافت اطلاعات...
            </div>

            <div v-else-if="products.length === 0" class="p-8 text-center">
                <p class="font-medium">هنوز محصولی ثبت نشده است.</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    برای شروع، اولین محصول را ایجاد کنید.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="bg-muted/50 text-right text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">تصویر</th>
                            <th class="px-4 py-3 font-medium">نام</th>
                            <th class="px-4 py-3 font-medium">Slug</th>
                            <th class="px-4 py-3 font-medium">دسته‌بندی‌ها</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">ترتیب</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="product in products" :key="product.id">
                            <td class="px-4 py-3">
                                <img
                                    v-if="product.images.length"
                                    :src="product.images[0].url"
                                    :alt="product.images[0].alt ?? product.name"
                                    class="size-12 rounded-md object-cover"
                                />
                                <div
                                    v-else
                                    class="flex size-12 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground"
                                >
                                    —
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ product.name }}
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground"
                                dir="ltr"
                            >
                                {{ product.slug }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    product.categories
                                        .map((category) => category.name)
                                        .join('، ') || '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        product.is_active
                                            ? 'text-green-600'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{
                                        product.is_active ? 'فعال' : 'پیش‌نویس'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ product.sort_order }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="text-primary hover:underline"
                                        @click="openEditForm(product)"
                                    >
                                        ویرایش
                                    </button>
                                    <button
                                        type="button"
                                        class="text-destructive hover:underline disabled:opacity-50"
                                        :disabled="deletingId === product.id"
                                        @click="deleteProduct(product)"
                                    >
                                        {{
                                            deletingId === product.id
                                                ? '...'
                                                : 'حذف'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="pagination && (pagination.prev || pagination.next)"
                class="flex items-center justify-between border-t px-4 py-3"
            >
                <button
                    type="button"
                    :disabled="!pagination.prev || loading"
                    class="rounded-md border px-3 py-1.5 text-sm disabled:opacity-40"
                    @click="pagination.prev && loadProducts(pagination.prev)"
                >
                    قبلی
                </button>
                <button
                    type="button"
                    :disabled="!pagination.next || loading"
                    class="rounded-md border px-3 py-1.5 text-sm disabled:opacity-40"
                    @click="pagination.next && loadProducts(pagination.next)"
                >
                    بعدی
                </button>
            </div>
        </section>
    </div>
</template>
