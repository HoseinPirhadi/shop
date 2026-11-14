<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'تأیید نشانی ایمیل',
        description:
            'برای تأیید نشانی ایمیل، پیوندی را که برایتان فرستاده‌ایم باز کنید.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="تأیید نشانی ایمیل" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        پیوند تازه تأیید به نشانی ایمیلی که هنگام ثبت‌نام وارد کرده‌اید ارسال
        شد.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            ارسال دوباره ایمیل تأیید
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            خروج از حساب
        </TextLink>
    </Form>
</template>
