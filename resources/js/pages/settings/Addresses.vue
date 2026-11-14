<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type Address = {
    id: number;
    label: string;
    recipient_name: string;
    phone: string;
    country_code: string;
    state: string | null;
    city: string;
    address_line1: string;
    address_line2: string | null;
    postal_code: string | null;
    is_default: boolean;
};

type ApiError = {
    message?: string;
    errors?: Record<string, string[]>;
};

type AddressForm = {
    label: string;
    recipient_name: string;
    phone: string;
    country_code: string;
    state: string;
    city: string;
    address_line1: string;
    address_line2: string;
    postal_code: string;
    is_default: boolean;
};

const addresses = ref<Address[]>([]);
const loading = ref(true);
const saving = ref(false);
const deletingId = ref<number | null>(null);
const selectedId = ref<number | null>(null);
const formVisible = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

const emptyForm = (): AddressForm => ({
    label: 'خانه',
    recipient_name: '',
    phone: '',
    country_code: '',
    state: '',
    city: '',
    address_line1: '',
    address_line2: '',
    postal_code: '',
    is_default: false,
});

const form = ref<AddressForm>(emptyForm());

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

    if (response.status === 204) return undefined as T;

    const payload = (await response.json()) as T & ApiError;

    if (!response.ok) throw payload;

    return payload;
}

async function loadAddresses() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const result = await apiRequest<{ data: Address[] }>(
            '/api/v1/me/addresses',
        );
        addresses.value = result.data;
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'دریافت نشانی‌ها انجام نشد.';
    } finally {
        loading.value = false;
    }
}

function openCreateForm() {
    selectedId.value = null;
    form.value = emptyForm();
    fieldErrors.value = {};
    formVisible.value = true;
}

function openEditForm(address: Address) {
    selectedId.value = address.id;
    form.value = {
        label: address.label,
        recipient_name: address.recipient_name,
        phone: address.phone,
        country_code: address.country_code,
        state: address.state ?? '',
        city: address.city,
        address_line1: address.address_line1,
        address_line2: address.address_line2 ?? '',
        postal_code: address.postal_code ?? '',
        is_default: address.is_default,
    };
    fieldErrors.value = {};
    formVisible.value = true;
}

function closeForm() {
    formVisible.value = false;
    selectedId.value = null;
    fieldErrors.value = {};
}

async function saveAddress() {
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    fieldErrors.value = {};

    const url = selectedId.value
        ? '/api/v1/me/addresses/' + selectedId.value
        : '/api/v1/me/addresses';

    try {
        await apiRequest(url, {
            method: selectedId.value ? 'PATCH' : 'POST',
            body: JSON.stringify({
                ...form.value,
                country_code: form.value.country_code.toUpperCase(),
                state: form.value.state.trim() || null,
                address_line2: form.value.address_line2.trim() || null,
                postal_code: form.value.postal_code.trim() || null,
            }),
        });

        closeForm();
        successMessage.value = 'نشانی ذخیره شد.';
        await loadAddresses();
    } catch (error) {
        const apiError = error as ApiError;
        fieldErrors.value = apiError.errors ?? {};
        errorMessage.value = apiError.errors
            ? ''
            : (apiError.message ?? 'ذخیره نشانی انجام نشد.');
    } finally {
        saving.value = false;
    }
}

async function setDefault(address: Address) {
    errorMessage.value = '';

    try {
        await apiRequest('/api/v1/me/addresses/' + address.id, {
            method: 'PATCH',
            body: JSON.stringify({ is_default: true }),
        });
        await loadAddresses();
        successMessage.value = 'نشانی پیش‌فرض تغییر کرد.';
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'تغییر نشانی پیش‌فرض انجام نشد.';
    }
}

async function deleteAddress(address: Address) {
    if (!window.confirm('نشانی «' + address.label + '» حذف شود؟')) return;

    deletingId.value = address.id;
    errorMessage.value = '';

    try {
        await apiRequest('/api/v1/me/addresses/' + address.id, {
            method: 'DELETE',
        });
        await loadAddresses();
        successMessage.value = 'نشانی حذف شد.';
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'حذف نشانی انجام نشد.';
    } finally {
        deletingId.value = null;
    }
}

onMounted(loadAddresses);
</script>

<template>
    <Head title="نشانی‌های من" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">نشانی‌های من</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    نشانی‌های تحویل سفارش‌های آینده را مدیریت کنید.
                </p>
            </div>
            <button
                type="button"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                @click="openCreateForm"
            >
                افزودن نشانی
            </button>
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

        <section v-if="formVisible" class="rounded-xl border p-4">
            <h2 class="mb-4 font-semibold">
                {{ selectedId ? 'ویرایش نشانی' : 'نشانی جدید' }}
            </h2>

            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="saveAddress">
                <label class="grid gap-1.5 text-sm">
                    عنوان نشانی
                    <input
                        v-model="form.label"
                        required
                        maxlength="80"
                        class="h-10 rounded-md border bg-background px-3"
                        placeholder="خانه، محل کار..."
                    />
                    <span v-if="fieldErrors.label" class="text-xs text-destructive">
                        {{ fieldErrors.label[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    نام تحویل‌گیرنده
                    <input
                        v-model="form.recipient_name"
                        required
                        maxlength="160"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span
                        v-if="fieldErrors.recipient_name"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.recipient_name[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    شماره تماس
                    <input
                        v-model="form.phone"
                        required
                        maxlength="30"
                        dir="ltr"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span v-if="fieldErrors.phone" class="text-xs text-destructive">
                        {{ fieldErrors.phone[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    کد کشور (ISO 3166-1 alpha-2)
                    <input
                        v-model="form.country_code"
                        required
                        minlength="2"
                        maxlength="2"
                        dir="ltr"
                        class="h-10 rounded-md border bg-background px-3 uppercase"
                        placeholder="GB"
                        @input="
                            form.country_code = form.country_code
                                .toUpperCase()
                                .slice(0, 2)
                        "
                    />
                    <span
                        v-if="fieldErrors.country_code"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.country_code[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm">
                    استان / ایالت
                    <input
                        v-model="form.state"
                        maxlength="120"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                </label>

                <label class="grid gap-1.5 text-sm">
                    شهر
                    <input
                        v-model="form.city"
                        required
                        maxlength="120"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span v-if="fieldErrors.city" class="text-xs text-destructive">
                        {{ fieldErrors.city[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm md:col-span-2">
                    نشانی اصلی
                    <input
                        v-model="form.address_line1"
                        required
                        maxlength="255"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span
                        v-if="fieldErrors.address_line1"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.address_line1[0] }}
                    </span>
                </label>

                <label class="grid gap-1.5 text-sm md:col-span-2">
                    نشانی تکمیلی
                    <input
                        v-model="form.address_line2"
                        maxlength="255"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                </label>

                <label class="grid gap-1.5 text-sm">
                    کد پستی
                    <input
                        v-model="form.postal_code"
                        maxlength="32"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                </label>

                <label class="flex items-center gap-2 self-end text-sm">
                    <input v-model="form.is_default" type="checkbox" class="size-4" />
                    نشانی پیش‌فرض من باشد
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
                        {{ saving ? 'در حال ذخیره...' : 'ذخیره نشانی' }}
                    </button>
                </div>
            </form>
        </section>

        <p v-if="loading" class="py-6 text-center text-sm text-muted-foreground">
            در حال دریافت نشانی‌ها...
        </p>

        <div
            v-else-if="addresses.length === 0"
            class="rounded-xl border border-dashed p-8 text-center"
        >
            <p class="font-medium">هنوز نشانی‌ای ثبت نکرده‌اید.</p>
            <p class="mt-1 text-sm text-muted-foreground">
                برای ثبت نشانی تحویل، گزینه افزودن نشانی را انتخاب کنید.
            </p>
        </div>

        <div v-else class="grid gap-3">
            <article
                v-for="address in addresses"
                :key="address.id"
                class="rounded-xl border p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <h2 class="font-semibold">{{ address.label }}</h2>
                        <span
                            v-if="address.is_default"
                            class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary"
                        >
                            پیش‌فرض
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <button
                            type="button"
                            class="text-primary hover:underline"
                            @click="openEditForm(address)"
                        >
                            ویرایش
                        </button>
                        <button
                            v-if="!address.is_default"
                            type="button"
                            class="text-primary hover:underline"
                            @click="setDefault(address)"
                        >
                            انتخاب به‌عنوان پیش‌فرض
                        </button>
                        <button
                            type="button"
                            class="text-destructive hover:underline disabled:opacity-50"
                            :disabled="deletingId === address.id"
                            @click="deleteAddress(address)"
                        >
                            {{ deletingId === address.id ? '...' : 'حذف' }}
                        </button>
                    </div>
                </div>

                <p class="mt-3 text-sm font-medium">
                    {{ address.recipient_name }} · {{ address.phone }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        [
                            address.address_line1,
                            address.address_line2,
                            address.city,
                            address.state,
                            address.postal_code,
                            address.country_code,
                        ]
                            .filter(Boolean)
                            .join('، ')
                    }}
                </p>
            </article>
        </div>
    </div>
</template>
