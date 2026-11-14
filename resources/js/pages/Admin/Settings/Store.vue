<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type StoreSettings = {
    store_name: string | null;
    currency_code: string | null;
    currency_configured: boolean;
};

type ApiError = {
    message?: string;
    errors?: Record<string, string[]>;
};

const form = ref({
    store_name: '',
    currency_code: '',
});
const currencyConfigured = ref(false);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});
const successMessage = ref('');

function csrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

async function apiRequest<T>(
    url: string,
    options: RequestInit = {},
): Promise<T> {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
            ...(options.headers as Record<string, string> | undefined),
        },
    });

    const payload = (await response.json()) as T & ApiError;

    if (!response.ok) {
        throw payload;
    }

    return payload;
}

async function loadSettings() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const result = await apiRequest<{ data: StoreSettings }>(
            '/api/v1/admin/settings/store',
        );

        form.value.store_name = result.data.store_name ?? '';
        form.value.currency_code = result.data.currency_code ?? '';
        currencyConfigured.value = result.data.currency_configured;
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ??
            'دریافت تنظیمات فروشگاه انجام نشد.';
    } finally {
        loading.value = false;
    }
}

async function saveSettings() {
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    fieldErrors.value = {};

    try {
        const result = await apiRequest<{ data: StoreSettings }>(
            '/api/v1/admin/settings/store',
            {
                method: 'PATCH',
                body: JSON.stringify({
                    store_name: form.value.store_name.trim() || null,
                    currency_code:
                        form.value.currency_code.trim().toUpperCase() || null,
                }),
            },
        );

        form.value.store_name = result.data.store_name ?? '';
        form.value.currency_code = result.data.currency_code ?? '';
        currencyConfigured.value = result.data.currency_configured;
        successMessage.value = 'تنظیمات فروشگاه ذخیره شد.';
    } catch (error) {
        const apiError = error as ApiError;
        fieldErrors.value = apiError.errors ?? {};
        errorMessage.value = apiError.errors
            ? ''
            : (apiError.message ?? 'ذخیره تنظیمات انجام نشد.');
    } finally {
        saving.value = false;
    }
}
 
onMounted(loadSettings);
</script>

<template>
    <Head title="تنظیمات فروشگاه" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold">تنظیمات فروشگاه</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                اطلاعات پایه و واحد پول مورد استفاده در فروشگاه
            </p>
        </div>

        <div
            v-if="errorMessage"
            role="alert"
            class="rounded-md border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
        >
            {{ errorMessage }}
        </div>

        <div
            v-if="successMessage"
            role="status"
            class="rounded-md border px-4 py-3 text-sm"
        >
            {{ successMessage }}
        </div>

        <section class="rounded-xl border bg-card p-4 md:p-6">
            <p v-if="loading" class="text-sm text-muted-foreground">
                در حال دریافت تنظیمات...
            </p>

            <form v-else class="grid gap-5" @submit.prevent="saveSettings">
                <label class="grid gap-1.5 text-sm">
                    نام فروشگاه
                    <input
                        v-model="form.store_name"
                        maxlength="160"
                        class="h-10 rounded-md border bg-background px-3"
                        placeholder="نام فروشگاه"
                    />
                    <span
                        v-if="fieldErrors.store_name"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.store_name[0] }}
                    </span>
                </label>

                <label class="grid max-w-xs gap-1.5 text-sm">
                    کد ارز فروشگاه
                    <input
                        v-model="form.currency_code"
                        maxlength="3"
                        minlength="3"
                        dir="ltr"
                        class="h-10 rounded-md border bg-background px-3 uppercase"
                        placeholder="GBP"
                        autocomplete="off"
                        @input="
                            form.currency_code = form.currency_code
                                .toUpperCase()
                                .slice(0, 3)
                        "
                    />
                    <span class="text-xs text-muted-foreground">
                        کد سه‌حرفی ارز بر اساس قالب ISO 4217؛ مانند GBP، EUR،
                        USD یا IRR
                    </span>
                    <span
                        v-if="fieldErrors.currency_code"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.currency_code[0] }}
                    </span>
                </label>

                <div class="rounded-lg bg-muted/50 p-4 text-sm">
                    <p v-if="currencyConfigured" class="font-medium">
                        کد ارز ثبت شده است: {{ form.currency_code }}
                    </p>
                    <p v-else class="font-medium">
                        هنوز واحد پول فروشگاه تعیین نشده است.
                    </p>
                    <p class="mt-1 text-muted-foreground">
                        تا زمان تعیین ارز، قیمت‌گذاری و خرید فعال نمی‌شوند.
                        ثبت کد ارز به‌تنهایی به معنی فعال‌شدن پرداخت یا
                        فرایند سفارش نیست.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        {{ saving ? 'در حال ذخیره...' : 'ذخیره تنظیمات' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</template>
