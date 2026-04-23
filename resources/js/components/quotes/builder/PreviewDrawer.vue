<script setup lang="ts">
import { computed } from 'vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type {
    BrandingData,
    QuoteBuilderState,
    TemplateLayout,
} from '@/types';

const props = defineProps<{
    open: boolean;
    mode: 'quote' | 'template';
    state: QuoteBuilderState;
    currentLayout: TemplateLayout;
    branding?: {
        company_name: string | null;
        logo_url: string | null;
        primary_color: string;
        accent_color: string;
        company_email: string | null;
        company_phone: string | null;
        company_address: string | null;
        company_tagline: string | null;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const brandingData = computed<BrandingData>(() => {
    return {
        company_name: props.branding?.company_name ?? null,
        logo_url: props.branding?.logo_url ?? null,
        primary_color: props.branding?.primary_color ?? '#2563EB',
        accent_color: props.branding?.accent_color ?? '#F59E0B',
        company_email: props.branding?.company_email ?? null,
        company_phone: props.branding?.company_phone ?? null,
        company_address: props.branding?.company_address ?? null,
        company_tagline: props.branding?.company_tagline ?? null,
    };
});
</script>

<template>
    <Sheet :open="open" @update:open="(value) => emit('update:open', value)">
        <SheetContent side="right" class="w-full overflow-y-auto bg-muted/30 sm:max-w-4xl xl:max-w-6xl">
            <div class="space-y-6">
                <SheetHeader>
                    <SheetTitle>Client preview</SheetTitle>
                    <SheetDescription>
                        This preview matches what your client will see.
                    </SheetDescription>
                </SheetHeader>

                <div class="mx-auto max-w-4xl rounded-lg border bg-white p-6 shadow-sm">
                    <QuoteRenderer
                        :quote="state"
                        :layout="currentLayout"
                        :branding="brandingData"
                        :preview-mode="true"
                    />
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
