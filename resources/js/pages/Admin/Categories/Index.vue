<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    sort_order: number;
    is_active: boolean;
    children_count?: number;
};

type CategoryPage = {
    data: Category[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
};

type CategoryForm = {
    name: string;
    slug: string;
    description: string;
    parent_id: string;
    sort_order: number;
    is_active: boolean;
};

type ApiError = {
    message?: string;
    errors?: Record<string, string[]>;
};

const categories = ref<Category[]>([]);
const pagination = ref<CategoryPage['links'] | null>(null);
const loading = ref(true);
const saving = ref(false);
const deletingId = ref<number | null>(null);
const errorMessage = ref('');
const formVisible = ref(false);
const editingId = ref<number | null>(null);
const fieldErrors = ref<Record<string, string[]>>({});

const emptyForm = (): CategoryForm => ({
    name: '',
    slug: '',
    description: '',
    parent_id: '',
    sort_order: 0,
    is_active: true,
});

const form = ref<CategoryForm>(emptyForm());

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

async function loadCategories(url = '/api/v1/admin/categories') {
    loading.value = true;
    errorMessage.value = '';

    try {
        const result = await apiRequest<CategoryPage>(url);
        categories.value = result.data;
        pagination.value = result.links;
    } catch {
        errorMessage.value =
            'دریافت فهرست دسته‌بندی‌ها ناموفق بود. صفحه را دوباره بارگذاری کنید.';
    } finally {
        loading.value = false;
    }
}

function openCreateForm() {
    editingId.value = null;
    form.value = emptyForm();
    fieldErrors.value = {};
    formVisible.value = true;
}

function openEditForm(category: Category) {
    editingId.value = category.id;
    form.value = {
        name: category.name,
        slug: category.slug,
        description: category.description ?? '',
        parent_id: category.parent_id?.toString() ?? '',
        sort_order: category.sort_order,
        is_active: category.is_active,
    };
    fieldErrors.value = {};
    formVisible.value = true;
}

function closeForm() {
    formVisible.value = false;
    fieldErrors.value = {};
}

async function saveCategory() {
    saving.value = true;
    fieldErrors.value = {};

    const payload = {
        ...form.value,
        parent_id: form.value.parent_id ? Number(form.value.parent_id) : null,
        sort_order: Number(form.value.sort_order),
    };
    const url = editingId.value
        ? `/api/v1/admin/categories/${editingId.value}`
        : '/api/v1/admin/categories';

    try {
        await apiRequest(url, {
            method: editingId.value ? 'PATCH' : 'POST',
            body: JSON.stringify(payload),
        });
        closeForm();
        await loadCategories();
    } catch (error) {
        const apiError = error as ApiError;
        fieldErrors.value = apiError.errors ?? {};
        errorMessage.value = apiError.errors
            ? ''
            : (apiError.message ?? 'ذخیره دسته‌بندی انجام نشد.');
    } finally {
        saving.value = false;
    }
}

async function deleteCategory(category: Category) {
    if (!window.confirm(`دسته‌بندی «${category.name}» حذف شود؟`)) {
        return;
    }

    deletingId.value = category.id;
    errorMessage.value = '';

    try {
        await apiRequest(`/api/v1/admin/categories/${category.id}`, {
            method: 'DELETE',
        });
        await loadCategories();
    } catch (error) {
        const apiError = error as ApiError;
        errorMessage.value = apiError.message ?? 'حذف دسته‌بندی انجام نشد.';
    } finally {
        deletingId.value = null;
    }
}

function depth(category: Category): number {
    let level = 0;
    let parentId = category.parent_id;
    const visited = new Set<number>();

    while (parentId !== null && !visited.has(parentId)) {
        visited.add(parentId);
        const parent = categories.value.find((item) => item.id === parentId);

        if (!parent) {
            break;
        }

        level += 1;
        parentId = parent.parent_id;
    }

    return level;
}

onMounted(() => loadCategories());
</script>

<template>
    <Head title="مدیریت دسته‌بندی‌ها" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">مدیریت دسته‌بندی‌ها</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    ایجاد و سازمان‌دهی دسته‌بندی‌های چندسطحی محصولات
                </p>
            </div>

            <button
                type="button"
                class="admin-primary-action rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                @click="openCreateForm"
            >
                دسته‌بندی جدید
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
                    {{ editingId ? 'ویرایش دسته‌بندی' : 'ایجاد دسته‌بندی' }}
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
                @submit.prevent="saveCategory"
            >
                <label class="grid gap-1.5 text-sm">
                    نام دسته‌بندی
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
                    شناسه نشانی دسته‌بندی
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
                    دسته‌بندی والد
                    <select
                        v-model="form.parent_id"
                        class="h-10 rounded-md border bg-background px-3"
                    >
                        <option value="">بدون والد (دسته اصلی)</option>
                        <option
                            v-for="category in categories.filter(
                                (item) => item.id !== editingId,
                            )"
                            :key="category.id"
                            :value="category.id.toString()"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <span
                        v-if="fieldErrors.parent_id"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.parent_id[0] }}
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
                        rows="3"
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

            <div v-else-if="categories.length === 0" class="p-8 text-center">
                <p class="font-medium">هنوز دسته‌بندی‌ای ثبت نشده است.</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    برای شروع، اولین دسته‌بندی را ایجاد کنید.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-muted/50 text-right text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">نام</th>
                            <th class="px-4 py-3 font-medium">شناسه نشانی</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">زیردسته</th>
                            <th class="px-4 py-3 font-medium">ترتیب</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="category in categories" :key="category.id">
                            <td class="px-4 py-3 font-medium">
                                <span
                                    :style="{
                                        paddingInlineStart: `${depth(category) * 20}px`,
                                    }"
                                >
                                    {{ category.name }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground"
                                dir="ltr"
                            >
                                {{ category.slug }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="admin-status"
                                    :class="category.is_active ? 'is-active' : 'is-inactive'"
                                >
                                    {{
                                        category.is_active ? 'فعال' : 'غیرفعال'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ category.children_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3">{{ category.sort_order }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="admin-row-action admin-row-action-edit text-primary hover:underline"
                                        @click="openEditForm(category)"
                                    >
                                        ویرایش
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-row-action admin-row-action-delete text-destructive hover:underline disabled:opacity-50"
                                        :disabled="deletingId === category.id"
                                        @click="deleteCategory(category)"
                                    >
                                        {{
                                            deletingId === category.id
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
                    @click="pagination.prev && loadCategories(pagination.prev)"
                >
                    قبلی
                </button>
                <button
                    type="button"
                    :disabled="!pagination.next || loading"
                    class="rounded-md border px-3 py-1.5 text-sm disabled:opacity-40"
                    @click="pagination.next && loadCategories(pagination.next)"
                >
                    بعدی
                </button>
            </div>
        </section>
    </div>
</template>
