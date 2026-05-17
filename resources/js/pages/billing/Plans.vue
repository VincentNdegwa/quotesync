<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Check, Minus } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { subscribe as billingSubscribe } from '@/routes/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing' },
            { title: 'Plans', href: '/billing/subscribe' },
        ],
    },
});

const page = usePage();
const workspace = computed(() => page.props.workspace);
const plans = computed(() => page.props.plans);
const features = computed(() => page.props.features);
const currentPlanId = computed(() => workspace.value?.plan_id);

const popularPlanId = computed(() => {
    const paid = plans.value?.filter((p) => p.monthly_price > 0) ?? [];

    return paid[1]?.id ?? paid[0]?.id ?? null;
});

const formatPrice = (price: number): string => {
    if (price === 0) {
        return 'Free';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(price);
};

const visibleRows = computed(() => {
    return (
        features.value?.filter((row) =>
            plans.value?.some((plan) => {
                const v = plan.features?.[row.key];

                return v !== false && v !== undefined;
            }),
        ) ?? []
    );
});

const cellValue = (plan: any, key: string): { type: string; text?: string } => {
    const v = plan.features?.[key];

    if (v === undefined || v === false) {
        return { type: 'none' };
    }

    if (v === null) {
        return { type: 'value', text: 'Unlimited' };
    }

    if (v === true) {
        return { type: 'check' };
    }

    if (typeof v === 'number') {
        return { type: 'value', text: v.toLocaleString() };
    }

    return { type: 'value', text: String(v) };
};

const selectPlan = (planSlug: string): void => {
    router.get(billingSubscribe(planSlug).url);
};
</script>

<template>
    <Head title="Plans" />

    <div class="space-y-8">
        <Heading
            title="Choose Your Plan"
            description="Select the perfect plan for your needs"
        />

        <div class="w-full overflow-x-auto bg-card">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="w-48 px-6 py-6 text-left align-bottom">
                            <span class="sr-only">Feature</span>
                        </th>
                        <th
                            v-for="plan in plans"
                            :key="plan.id"
                            class="px-6 py-6 text-center align-bottom"
                            :class="
                                plan.id === popularPlanId
                                    ? 'rounded-t-xl bg-primary text-primary-foreground'
                                    : ''
                            "
                        >
                            <div class="space-y-3">
                                <div class="h-5">
                                    <Badge
                                        v-if="plan.id === popularPlanId"
                                        variant="secondary"
                                        class="text-xs"
                                        >Popular</Badge
                                    >
                                </div>
                                <p class="text-base font-semibold">
                                    {{ plan.name }}
                                </p>
                                <div>
                                    <p class="text-3xl font-bold tabular-nums">
                                        {{ formatPrice(plan.monthly_price) }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs"
                                        :class="
                                            plan.id === popularPlanId
                                                ? 'text-primary-foreground/70'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            plan.monthly_price > 0
                                                ? 'Per month'
                                                : 'Forever free'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, rowIndex) in visibleRows"
                        :key="row.key"
                        class="border-t border-border"
                        :class="
                            rowIndex % 2 === 0
                                ? 'bg-transparent'
                                : 'bg-muted/20'
                        "
                    >
                        <td class="px-6 py-3.5 font-medium text-foreground">
                            {{ row.label }}
                        </td>
                        <td
                            v-for="plan in plans"
                            :key="plan.id"
                            class="px-6 py-3.5 text-center"
                            :class="
                                plan.id === popularPlanId ? 'bg-primary/5' : ''
                            "
                        >
                            <div
                                v-if="cellValue(plan, row.key).type === 'check'"
                                class="flex justify-center"
                            >
                                <Check
                                    class="h-4 w-4"
                                    :class="
                                        plan.id === popularPlanId
                                            ? 'text-primary'
                                            : 'text-foreground'
                                    "
                                />
                            </div>
                            <div
                                v-else-if="
                                    cellValue(plan, row.key).type === 'none'
                                "
                                class="flex justify-center"
                            >
                                <Minus
                                    class="h-4 w-4 text-muted-foreground/40"
                                />
                            </div>
                            <span
                                v-else
                                class="font-medium tabular-nums"
                                :class="
                                    plan.id === popularPlanId
                                        ? 'text-primary'
                                        : 'text-foreground'
                                "
                            >
                                {{ cellValue(plan, row.key).text }}
                            </span>
                        </td>
                    </tr>
                    <tr class="border-t border-border">
                        <td class="px-6 py-5 text-xs text-muted-foreground">
                            All plans include core features
                        </td>
                        <td
                            v-for="plan in plans"
                            :key="plan.id"
                            class="px-6 py-5 text-center"
                            :class="
                                plan.id === popularPlanId ? 'bg-primary/5' : ''
                            "
                        >
                            <Button
                                class="w-full"
                                :variant="
                                    plan.id === popularPlanId
                                        ? 'default'
                                        : currentPlanId === plan.id
                                          ? 'outline'
                                          : 'outline'
                                "
                                :disabled="currentPlanId === plan.id"
                                @click="selectPlan(plan.slug)"
                            >
                                {{
                                    currentPlanId === plan.id
                                        ? 'Current Plan'
                                        : 'Get Started'
                                }}
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
