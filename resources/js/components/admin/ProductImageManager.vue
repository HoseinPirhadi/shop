<script setup lang="ts">
import { ref } from 'vue';

type ProductImage = {
    id: number;
    url: string;
    alt: string | null;
    sort_order: number;
    is_primary: boolean;
};

type ApiError = {
    message?: string;
};

const props = defineProps<{
    productId: number;
    images: ProductImage[];
}>();

const emit = defineEmits<{
    'update:images': [images: ProductImage[]];
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const alt = ref('');
const uploading = ref(false);
const deletingId = ref<number | null>(null);
const updatingId = ref<number | null>(null);
const errorMessage = ref('');

function selectFile(event: Event) {
    const target = event.target;

    selectedFile.value =
        target instanceof HTMLInputElement
            ? (target.files?.[0] ?? null)
            : null;
}

function csrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie
        ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length))
        : '';
}

async function uploadImage() {
    if (!selectedFile.value) return;

    uploading.value = true;
    errorMessage.value = '';

    const formData = new FormData();
    formData.append('image', selectedFile.value);
    formData.append('alt', alt.value);
    formData.append('sort_order', String(props.images.length * 10));

    try {
        const response = await fetch(
            '/api/v1/admin/products/' + props.productId + '/images',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: formData,
            },
        );

        const payload = (await response.json()) as {
            data: ProductImage;
        } & ApiError;

        if (!response.ok) {
            throw payload;
        }

        emit('update:images', [...props.images, payload.data]);
        selectedFile.value = null;
        alt.value = '';

        if (fileInput.value) fileInput.value.value = '';
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'بارگذاری تصویر انجام نشد.';
    } finally {
        uploading.value = false;
    }
}

async function deleteImage(image: ProductImage) {
    if (!window.confirm('این تصویر حذف شود؟')) return;

    deletingId.value = image.id;
    errorMessage.value = '';

    try {
        const response = await fetch(
            '/api/v1/admin/products/' +
                props.productId +
                '/images/' +
                image.id,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
            },
        );

        const payload = (await response.json()) as {
            data: ProductImage[];
        } & ApiError;

        if (!response.ok) {
            throw payload;
        }

        emit('update:images', payload.data);
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'حذف تصویر انجام نشد.';
    } finally {
        deletingId.value = null;
    }
}

async function setPrimary(image: ProductImage) {
    if (image.is_primary) return;

    updatingId.value = image.id;
    errorMessage.value = '';

    try {
        const response = await fetch(
            '/api/v1/admin/products/' +
                props.productId +
                '/images/' +
                image.id,
            {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ is_primary: true }),
            },
        );

        const payload = (await response.json()) as {
            data: ProductImage;
        } & ApiError;

        if (!response.ok) {
            throw payload;
        }

        emit(
            'update:images',
            props.images.map((item) => ({
                ...item,
                is_primary: item.id === payload.data.id,
            })),
        );
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'تغییر تصویر اصلی انجام نشد.';
    } finally {
        updatingId.value = null;
    }
}
</script>

<template>
    <section class="rounded-lg border p-4">
        <div class="mb-4">
            <h3 class="font-semibold">تصاویر محصول</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                فرمت‌های JPG، PNG و WebP؛ حداکثر ۵ مگابایت برای هر تصویر.
            </p>
        </div>

        <div
            v-if="errorMessage"
            role="alert"
            class="mb-4 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive"
        >
            {{ errorMessage }}
        </div>

        <div v-if="images.length" class="mb-5 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <article
                v-for="image in images"
                :key="image.id"
                class="overflow-hidden rounded-lg border"
            >
                <img
                    :src="image.url"
                    :alt="image.alt ?? 'تصویر محصول'"
                    class="aspect-square w-full object-cover"
                />
                <div class="space-y-2 p-2">
                    <p class="truncate text-xs text-muted-foreground">
                        {{ image.alt || 'بدون توضیح' }}
                    </p>
                    <p
                        v-if="image.is_primary"
                        class="text-xs font-medium text-primary"
                    >
                        تصویر اصلی
                    </p>
                    <button
                        v-else
                        type="button"
                        :disabled="updatingId === image.id"
                        class="text-xs text-primary hover:underline disabled:opacity-50"
                        @click="setPrimary(image)"
                    >
                        {{ updatingId === image.id ? '...' : 'انتخاب به‌عنوان اصلی' }}
                    </button>
                    <button
                        type="button"
                        :disabled="deletingId === image.id"
                        class="block text-xs text-destructive hover:underline disabled:opacity-50"
                        @click="deleteImage(image)"
                    >
                        {{ deletingId === image.id ? 'در حال حذف...' : 'حذف تصویر' }}
                    </button>
                </div>
            </article>
        </div>
        <p v-else class="mb-5 rounded-md bg-muted/50 p-4 text-sm text-muted-foreground">
            هنوز تصویری برای این محصول بارگذاری نشده است.
        </p>

        <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="uploadImage">
            <label class="grid gap-1.5 text-sm sm:col-span-2">
                انتخاب تصویر
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    class="block w-full rounded-md border bg-background px-3 py-2 text-sm file:ml-3 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1"
                    @change="selectFile"
                />
            </label>
            <label class="grid gap-1.5 text-sm">
                توضیح تصویر (Alt)
                <input
                    v-model="alt"
                    maxlength="160"
                    class="h-10 rounded-md border bg-background px-3"
                    placeholder="توضیح کوتاه تصویر"
                />
            </label>
            <div class="flex items-end">
                <button
                    type="submit"
                    :disabled="!selectedFile || uploading"
                    class="h-10 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    {{ uploading ? 'در حال بارگذاری...' : 'بارگذاری تصویر' }}
                </button>
            </div>
        </form>
    </section>
</template>
