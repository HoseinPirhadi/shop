<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

type Variant = {
    id: number;
    name: string;
    sku: string | null;
    options: Record<string, string> | null;
    stock_quantity: number;
    track_inventory: boolean;
    is_active: boolean;
    is_default: boolean;
};

type Movement = {
    id: number;
    quantity_delta: number;
    quantity_before: number;
    quantity_after: number;
    reason: string;
    created_by: number | null;
    created_at: string;
};

type ApiError = {
    message?: string;
    errors?: Record<string, string[]>;
};

const props = defineProps<{ productId: number }>();
const variants = ref<Variant[]>([]);
const selectedId = ref<number | null>(null);
const movements = ref<Movement[]>([]);
const loading = ref(true);
const saving = ref(false);
const adjusting = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});
const quantityDelta = ref(0);
const reason = ref('');
const form = ref({
    name: '',
    sku: '',
    optionsText: '',
    track_inventory: true,
    is_active: true,
    is_default: false,
});
const selectedVariant = computed(
    () =>
        variants.value.find((variant) => variant.id === selectedId.value) ??
        null,
);

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

function toOptions(): Record<string, string> | null {
    const entries = form.value.optionsText
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
            const separator = line.indexOf('=');
            if (separator < 1) {
                throw new Error(
                    'هر ویژگی را در یک خط با قالب «نام=مقدار» وارد کنید.',
                );
            }
            return [
                line.slice(0, separator).trim(),
                line.slice(separator + 1).trim(),
            ];
        });

    return entries.length ? Object.fromEntries(entries) : null;
}

function loadIntoForm(variant: Variant | null) {
    fieldErrors.value = {};
    if (!variant) {
        form.value = {
            name: '',
            sku: '',
            optionsText: '',
            track_inventory: true,
            is_active: true,
            is_default: false,
        };
        return;
    }

    form.value = {
        name: variant.name,
        sku: variant.sku ?? '',
        optionsText: Object.entries(variant.options ?? {})
            .map(([key, value]) => key + '=' + value)
            .join('\n'),
        track_inventory: variant.track_inventory,
        is_active: variant.is_active,
        is_default: variant.is_default,
    };
}

async function loadMovements() {
    if (!selectedId.value) {
        movements.value = [];
        return;
    }

    const result = await apiRequest<{ data: Movement[] }>(
        '/api/v1/admin/products/' +
            props.productId +
            '/variants/' +
            selectedId.value +
            '/inventory-movements',
    );
    movements.value = result.data;
}

async function loadVariants() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const result = await apiRequest<{ data: Variant[] }>(
            '/api/v1/admin/products/' + props.productId + '/variants',
        );
        variants.value = result.data;
        selectedId.value = result.data[0]?.id ?? null;
        loadIntoForm(selectedVariant.value);
        await loadMovements();
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'دریافت گونه‌های محصول انجام نشد.';
    } finally {
        loading.value = false;
    }
}

function selectVariant(variant: Variant) {
    selectedId.value = variant.id;
    loadIntoForm(variant);
    void loadMovements().catch(() => {
        errorMessage.value = 'دریافت سوابق موجودی انجام نشد.';
    });
}

function newVariant() {
    selectedId.value = null;
    loadIntoForm(null);
    movements.value = [];
}

async function saveVariant() {
    saving.value = true;
    fieldErrors.value = {};
    errorMessage.value = '';

    try {
        const payload = {
            name: form.value.name,
            sku: form.value.sku || null,
            options: toOptions(),
            track_inventory: form.value.track_inventory,
            is_active: form.value.is_active,
            is_default: form.value.is_default,
        };
        const url = '/api/v1/admin/products/' + props.productId + '/variants';
        const result = await apiRequest<{ data: Variant }>(
            selectedId.value ? url + '/' + selectedId.value : url,
            {
                method: selectedId.value ? 'PATCH' : 'POST',
                body: JSON.stringify(payload),
            },
        );

        await loadVariants();
        const savedVariant = variants.value.find(
            (item) => item.id === result.data.id,
        );
        if (savedVariant) selectVariant(savedVariant);
    } catch (error) {
        const apiError = error as ApiError;
        fieldErrors.value = apiError.errors ?? {};
        errorMessage.value = apiError.errors
            ? ''
            : (apiError.message ?? 'ذخیره گونه انجام نشد.');
    } finally {
        saving.value = false;
    }
}

async function adjustInventory() {
    if (!selectedId.value || quantityDelta.value === 0 || !reason.value.trim())
        return;

    adjusting.value = true;
    errorMessage.value = '';

    try {
        await apiRequest(
            '/api/v1/admin/products/' +
                props.productId +
                '/variants/' +
                selectedId.value +
                '/inventory-adjustments',
            {
                method: 'POST',
                body: JSON.stringify({
                    quantity_delta: quantityDelta.value,
                    reason: reason.value,
                }),
            },
        );
        quantityDelta.value = 0;
        reason.value = '';
        const currentVariantId = selectedId.value;
        await loadVariants();
        const currentVariant = variants.value.find(
            (item) => item.id === currentVariantId,
        );
        if (currentVariant) {
            selectedId.value = currentVariant.id;
            loadIntoForm(currentVariant);
        }
        await loadMovements();
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'ثبت تغییر موجودی انجام نشد.';
    } finally {
        adjusting.value = false;
    }
}

async function deleteVariant(variant: Variant) {
    if (!window.confirm('گونه «' + variant.name + '» حذف شود؟')) return;

    try {
        await apiRequest(
            '/api/v1/admin/products/' +
                props.productId +
                '/variants/' +
                variant.id,
            { method: 'DELETE' },
        );
        await loadVariants();
    } catch (error) {
        errorMessage.value =
            (error as ApiError).message ?? 'حذف گونه انجام نشد.';
    }
}

onMounted(loadVariants);
</script>

<template>
    <section class="space-y-4 rounded-lg border p-4">
        <div>
            <h3 class="font-semibold">گونه‌ها و موجودی</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                هر گونه می‌تواند SKU و ویژگی‌های مستقل داشته باشد. تغییر موجودی
                با ثبت سابقه انجام می‌شود.
            </p>
        </div>

        <div
            v-if="errorMessage"
            role="alert"
            class="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive"
        >
            {{ errorMessage }}
        </div>

        <p v-if="loading" class="text-sm text-muted-foreground">
            در حال دریافت گونه‌ها...
        </p>
        <template v-else>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="variant in variants"
                    :key="variant.id"
                    type="button"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="
                        selectedId === variant.id
                            ? 'border-primary bg-primary/5'
                            : ''
                    "
                    @click="selectVariant(variant)"
                >
                    {{ variant.name }}
                    <span
                        v-if="variant.is_default"
                        class="text-xs text-primary"
                    >
                        (پیش‌فرض)</span
                    >
                </button>
                <button
                    type="button"
                    class="rounded-md border border-dashed px-3 py-2 text-sm"
                    @click="newVariant"
                >
                    افزودن گونه
                </button>
            </div>

            <form
                class="grid gap-3 md:grid-cols-2"
                @submit.prevent="saveVariant"
            >
                <label class="grid gap-1 text-sm">
                    نام گونه
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
                <label class="grid gap-1 text-sm">
                    SKU
                    <input
                        v-model="form.sku"
                        maxlength="100"
                        dir="ltr"
                        class="h-10 rounded-md border bg-background px-3"
                    />
                    <span
                        v-if="fieldErrors.sku"
                        class="text-xs text-destructive"
                    >
                        {{ fieldErrors.sku[0] }}
                    </span>
                </label>
                <label class="grid gap-1 text-sm md:col-span-2">
                    ویژگی‌ها (هر خط: نام=مقدار)
                    <textarea
                        v-model="form.optionsText"
                        rows="3"
                        class="rounded-md border bg-background px-3 py-2"
                        placeholder="رنگ=مشکی&#10;سایز=بزرگ"
                    />
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.track_inventory"
                        type="checkbox"
                        class="size-4"
                    />
                    کنترل موجودی فعال باشد
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4"
                    />
                    گونه فعال باشد
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_default"
                        type="checkbox"
                        class="size-4"
                    />
                    گونه پیش‌فرض
                </label>
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        {{ saving ? 'در حال ذخیره...' : 'ذخیره گونه' }}
                    </button>
                </div>
            </form>

            <div
                v-if="selectedVariant && selectedVariant.track_inventory"
                class="rounded-md bg-muted/40 p-3"
            >
                <p class="text-sm">
                    موجودی فعلی:
                    <strong>{{ selectedVariant.stock_quantity }}</strong>
                </p>
                <form
                    class="mt-3 grid gap-2 md:grid-cols-[1fr_2fr_auto]"
                    @submit.prevent="adjustInventory"
                >
                    <label class="grid gap-1 text-sm">
                        تغییر تعداد (+ یا -)
                        <input
                            v-model.number="quantityDelta"
                            type="number"
                            step="1"
                            required
                            class="h-10 rounded-md border bg-background px-3"
                        />
                    </label>
                    <label class="grid gap-1 text-sm">
                        دلیل تغییر
                        <input
                            v-model="reason"
                            required
                            maxlength="500"
                            class="h-10 rounded-md border bg-background px-3"
                            placeholder="مثلاً خرید جدید یا اصلاح شمارش"
                        />
                    </label>
                    <button
                        type="submit"
                        :disabled="
                            adjusting || quantityDelta === 0 || !reason.trim()
                        "
                        class="self-end rounded-md border px-4 py-2 text-sm disabled:opacity-50"
                    >
                        {{ adjusting ? '...' : 'ثبت موجودی' }}
                    </button>
                </form>
            </div>

            <div v-if="selectedVariant" class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium">سوابق تغییر موجودی</h4>
                    <button
                        v-if="variants.length > 1"
                        type="button"
                        class="text-xs text-destructive hover:underline"
                        @click="deleteVariant(selectedVariant)"
                    >
                        حذف این گونه
                    </button>
                </div>
                <div v-if="movements.length" class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead class="text-right text-muted-foreground">
                            <tr>
                                <th class="py-2">تغییر</th>
                                <th class="py-2">قبل</th>
                                <th class="py-2">بعد</th>
                                <th class="py-2">دلیل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="movement in movements"
                                :key="movement.id"
                            >
                                <td
                                    class="py-2"
                                    :class="
                                        movement.quantity_delta > 0
                                            ? 'text-green-600'
                                            : 'text-destructive'
                                    "
                                >
                                    {{ movement.quantity_delta > 0 ? '+' : ''
                                    }}{{ movement.quantity_delta }}
                                </td>
                                <td class="py-2">
                                    {{ movement.quantity_before }}
                                </td>
                                <td class="py-2">
                                    {{ movement.quantity_after }}
                                </td>
                                <td class="py-2">{{ movement.reason }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-xs text-muted-foreground">
                    هنوز تغییری برای این گونه ثبت نشده است.
                </p>
            </div>
        </template>
    </section>
</template>
