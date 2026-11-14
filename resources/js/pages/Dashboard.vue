<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Boxes, CircleAlert, FolderTree, Package, Plus, Settings2, ShoppingBag, Sparkles, Warehouse } from '@lucide/vue';
import { computed } from 'vue';

type Stats = { products: number; publishedProducts: number; categories: number; variants: number; trackedVariants: number; lowStock: number; outOfStock: number; availableStock: number };
type Product = { id: number; name: string; slug: string; is_active: boolean; created_at: string; images: { url: string; alt: string | null }[] };
type Movement = { id: number; quantity_delta: number; reason: string; product: { id: number; name: string } | null; variant: { id: number; name: string } | null; creator: { id: number; name: string } | null };
const props = defineProps<{ isAdmin: boolean; stats: Stats | null; recentProducts: Product[]; recentMovements: Movement[] }>();
const fa = (value: number) => new Intl.NumberFormat('fa-IR').format(value);
const today = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
const stockTotal = computed(() => props.stats?.trackedVariants ?? 0);
const stockParts = computed(() => [
    { title: 'موجود', value: props.stats?.availableStock ?? 0, color: '#10b981' },
    { title: 'رو به اتمام', value: props.stats?.lowStock ?? 0, color: '#f59e0b' },
    { title: 'ناموجود', value: props.stats?.outOfStock ?? 0, color: '#f43f5e' },
]);
const stockGradient = computed(() => {
    const total = stockTotal.value || 1;
    const a = (props.stats?.availableStock ?? 0) / total * 100;
    const b = a + (props.stats?.lowStock ?? 0) / total * 100;
    return `conic-gradient(#10b981 0 ${a}%, #f59e0b ${a}% ${b}%, #f43f5e ${b}% 100%)`;
});
const cards = computed(() => props.stats ? [
    { title: 'همه محصولات', value: props.stats.products, note: fa(props.stats.publishedProducts) + ' محصول فعال', icon: Package },
    { title: 'دسته‌بندی‌ها', value: props.stats.categories, note: 'ساختار کاتالوگ', icon: FolderTree },
    { title: 'گونه‌های فعال', value: props.stats.variants, note: 'قابل مدیریت', icon: Boxes },
    { title: 'نیازمند بررسی انبار', value: props.stats.lowStock + props.stats.outOfStock, note: fa(props.stats.outOfStock) + ' ناموجود', icon: CircleAlert },
] : []);
const shortDate = (value: string) => new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', month: 'short' }).format(new Date(value));
</script>

<template>
<Head title="داشبورد مدیریت" />
<main v-if="!isAdmin" class="flex min-h-[75vh] items-center justify-center p-6">
    <section class="w-full max-w-lg rounded-3xl border bg-card p-8 text-center shadow-sm">
        <div class="mx-auto mb-5 flex size-16 items-center justify-center rounded-2xl bg-primary/10 text-primary"><ShoppingBag class="size-8" /></div>
        <h1 class="text-2xl font-bold">به حساب کاربری خوش آمدید</h1>
        <p class="mt-3 text-sm leading-7 text-muted-foreground">این بخش برای پیگیری حساب و خریدهای شما آماده می‌شود. پس از فعال شدن سفارش‌ها، اطلاعات خرید در همین‌جا نمایش داده خواهد شد.</p>
        <Link href="/" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground">مشاهده فروشگاه <ArrowLeft class="size-4" /></Link>
    </section>
</main>
<main v-else-if="stats" class="min-h-full bg-[#f7f8fc] p-4 text-slate-800 md:p-7 dark:bg-slate-950 dark:text-slate-100">
<div class="mx-auto flex max-w-[1600px] flex-col gap-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div><div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400"><span>مدیریت فروشگاه</span><span class="size-1 rounded-full bg-slate-300"></span><span>{{ today }}</span></div><h1 class="text-2xl font-extrabold tracking-tight md:text-3xl">داشبورد فروشگاه</h1><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">نمای کلی محصولات، دسته‌بندی‌ها و وضعیت انبار</p></div>
        <div class="flex flex-wrap items-center gap-2"><Link href="/admin/products" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"><Plus class="size-4" /> افزودن محصول</Link><Link href="/admin/settings" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"><Settings2 class="size-4" /> تنظیمات فروشگاه</Link></div>
    </header>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in cards" :key="card.title" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between"><span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{card.title}}</span><span class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300"><component :is="card.icon" class="size-5" /></span></div>
            <div class="mt-5 flex items-end justify-between"><strong class="text-3xl font-extrabold tabular-nums">{{fa(card.value)}}</strong><span class="text-xs text-slate-400">{{card.note}}</span></div>
        </article>
    </section>
    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,1fr)]">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-6">
            <div class="flex items-start justify-between"><div><h2 class="text-lg font-bold">وضعیت موجودی انبار</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">بر اساس گونه‌های فعال با کنترل موجودی</p></div><Link href="/admin/products" class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">مدیریت محصولات</Link></div>
            <div class="mt-8 grid gap-8 md:grid-cols-[1fr_1.2fr] md:items-center">
                <div class="flex justify-center"><div class="flex size-48 items-center justify-center rounded-full" :style="{background:stockGradient}"><div class="flex size-36 flex-col items-center justify-center rounded-full bg-white dark:bg-slate-900"><strong class="text-3xl font-extrabold">{{fa(stockTotal)}}</strong><span class="mt-1 text-xs text-slate-400">گونه انباردار</span></div></div></div>
                <div class="space-y-5"><div v-for="part in stockParts" :key="part.title"><div class="mb-2 flex items-center justify-between text-sm"><span class="flex items-center gap-2"><span class="size-2.5 rounded-full" :style="{backgroundColor:part.color}"></span>{{part.title}}</span><strong>{{fa(part.value)}}</strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full" :style="{width:(part.value/(stockTotal||1)*100)+'%',backgroundColor:part.color}"></div></div></div><p v-if="stockTotal===0" class="text-xs text-slate-400">هنوز گونه‌ای با کنترل موجودی ثبت نشده است.</p></div>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-6">
            <div class="flex items-center justify-between"><div><h2 class="text-lg font-bold">میانبرهای مدیریتی</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">دسترسی سریع به بخش‌های اصلی</p></div><Warehouse class="size-5 text-indigo-500" /></div>
            <div class="mt-5 grid gap-3">
                <Link v-for="item in [{href:'/admin/products',title:'مدیریت محصولات',desc:'محصول، تصویر، گونه و موجودی',icon:Package},{href:'/admin/categories',title:'مدیریت دسته‌بندی‌ها',desc:'ساختاردهی کاتالوگ فروشگاه',icon:FolderTree},{href:'/admin/settings',title:'تنظیمات فروشگاه',desc:'نام فروشگاه و واحد پول',icon:Warehouse}]" :key="item.href" :href="item.href" class="group flex items-center justify-between rounded-xl border border-slate-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/60 dark:border-slate-800 dark:hover:bg-indigo-500/5"><span class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10"><component :is="item.icon" class="size-5" /></span><span><strong class="block text-sm">{{item.title}}</strong><small class="mt-1 block text-xs text-slate-400">{{item.desc}}</small></span></span><ArrowLeft class="size-4 text-slate-400" /></Link>
            </div>
        </article>
    </section>
    <section class="grid gap-6 xl:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800"><div><h2 class="font-bold">آخرین محصولات</h2><p class="mt-1 text-xs text-slate-400">تازه‌ترین محصولات ثبت‌شده</p></div><Link href="/admin/products" class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">مشاهده همه</Link></div>
            <div v-if="recentProducts.length" class="divide-y divide-slate-100 dark:divide-slate-800"><div v-for="product in recentProducts" :key="product.id" class="flex items-center justify-between gap-3 px-5 py-3.5"><div class="flex min-w-0 items-center gap-3"><img v-if="product.images[0]" :src="product.images[0].url" :alt="product.images[0].alt||product.name" class="size-11 rounded-xl object-cover" /><div v-else class="flex size-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><Package class="size-5" /></div><div class="min-w-0"><p class="truncate text-sm font-semibold">{{product.name}}</p><p class="mt-1 text-xs text-slate-400">{{shortDate(product.created_at)}}</p></div></div><span class="shrink-0 rounded-full px-3 py-1 text-xs" :class="product.is_active?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500'">{{product.is_active?'فعال':'پیش‌نویس'}}</span></div></div>
            <div v-else class="p-10 text-center text-sm text-slate-400">هنوز محصولی ثبت نشده است.</div>
        </article>
        <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800"><div><h2 class="font-bold">آخرین تغییرات انبار</h2><p class="mt-1 text-xs text-slate-400">سوابق تعدیل موجودی</p></div><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-500">{{fa(recentMovements.length)}} مورد</span></div>
            <div v-if="recentMovements.length" class="divide-y divide-slate-100 dark:divide-slate-800"><div v-for="movement in recentMovements" :key="movement.id" class="flex items-center justify-between gap-3 px-5 py-3.5"><div class="min-w-0"><p class="truncate text-sm font-semibold">{{movement.product?.name??'محصول حذف‌شده'}} <span class="font-normal text-slate-400">/ {{movement.variant?.name??'گونه حذف‌شده'}}</span></p><p class="mt-1 truncate text-xs text-slate-400">{{movement.reason}} · {{movement.creator?.name??'کاربر حذف‌شده'}}</p></div><span class="shrink-0 text-sm font-bold" :class="movement.quantity_delta>=0?'text-emerald-600':'text-rose-600'">{{movement.quantity_delta>0?'+':''}}{{fa(movement.quantity_delta)}}</span></div></div>
            <div v-else class="p-10 text-center text-sm text-slate-400">هنوز تغییری در موجودی ثبت نشده است.</div>
        </article>
    </section>
    <section class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-900 dark:bg-indigo-500/5"><div class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm"><Sparkles class="size-5" /></span><div><h2 class="font-bold">گزارش فروش و سفارش‌ها</h2><p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">پس از تکمیل قیمت‌گذاری، سبد خرید، ثبت سفارش و پرداخت، نمودار فروش، درآمد و آخرین سفارش‌ها به این داشبورد اضافه می‌شوند. در حال حاضر آمار فروش ساختگی نمایش داده نمی‌شود.</p></div></div></section>
</div>
</main>
</template>