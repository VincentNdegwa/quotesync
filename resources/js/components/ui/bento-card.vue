<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    name: string;
    className?: string;
    description: string;
    icon?: any;
    href?: string;
    cta?: string;
    featured?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
    featured: false,
    cta: 'Learn more',
    href: '#',
});
</script>

<template>
    <div
        :class="
            cn(
                'group relative flex flex-col justify-between overflow-hidden rounded-xl transition-all duration-300',
                'bg-background border border-border',
                featured
                    ? 'col-span-1 md:col-span-2 lg:col-span-2 border-primary/30 shadow-lg shadow-primary/10'
                    : '[box-shadow:0_0_0_1px_rgba(0,0,0,.03),0_2px_4px_rgba(0,0,0,.05),0_12px_24px_rgba(0,0,0,.05)]',
                'hover:shadow-xl hover:shadow-primary/5',
                className,
            )
        "
    >
        <div class="p-6">
            <div
                class="pointer-events-none z-10 mb-4 flex transform-gpu items-center gap-4 transition-all duration-300 lg:group-hover:-translate-y-2"
            >
                <div
                    :class="
                        cn(
                            'flex h-14 w-14 items-center justify-center rounded-xl',
                            featured
                                ? 'bg-primary/20'
                                : 'bg-muted border border-border',
                        )
                    "
                >
                    <component
                        v-if="icon"
                        :is="icon"
                        :class="cn('h-7 w-7', featured ? 'text-primary' : 'text-foreground')"
                    />
                </div>
                <h3
                    :class="
                        cn(
                            'text-xl font-semibold',
                            featured ? 'text-foreground' : 'text-foreground',
                        )
                    "
                >
                    {{ name }}
                </h3>
            </div>

            <p
                :class="
                    cn(
                        'max-w-lg text-sm leading-relaxed',
                        featured ? 'text-muted-foreground' : 'text-muted-foreground',
                    )
                "
            >
                {{ description }}
            </p>
        </div>

        <div
            :class="
                cn(
                    'pointer-events-none flex w-full transform-gpu flex-row items-center p-6 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100 opacity-0 translate-y-4',
                )
            "
        >
            <a
                :href="href"
                class="inline-flex items-center gap-2 text-sm font-medium text-primary transition-colors hover:text-primary/80"
            >
                {{ cta }}
                <svg
                    class="h-4 w-4 rtl:rotate-180"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                    />
                </svg>
            </a>
        </div>

        <div
            class="pointer-events-none absolute inset-0 transform-gpu transition-all duration-300 group-hover:bg-primary/3"
        />
    </div>
</template>
