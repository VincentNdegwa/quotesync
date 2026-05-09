<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const name = usePage().props.name
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center bg-background bg-dot-pattern px-4 py-12"
    >
        <div
            class="absolute inset-0 bg-[radial-gradient(64%_64%_at_50%_40%,rgba(255,255,255,0)_0%,#000000_100%)] dark:bg-[radial-gradient(64%_64%_at_50%_40%,rgba(0,0,0,0)_0%,#000000_100%)]"
            style="opacity: 0.05;"
        ></div>

        <div class="relative z-10 mx-auto w-full max-w-lg">
            <div class="flex flex-col items-center justify-center space-y-8">
                <Link
                    :href="home()"
                    class="flex flex-col items-center gap-2 font-medium"
                >
                <div class="flex justify-between items-center gap-5">
                    <div
                        class="mb-1 flex h-10 w-10 items-center justify-center rounded-md"
                    >
                        <AppLogoIcon
                            class="size-10 fill-current text-[var(--foreground)] dark:text-white"
                        />
                    </div>
                    <div class="font-bold text-2xl" >{{ name }}</div>

                </div>
                    <span class="sr-only">{{ title }}</span>
                </Link>

                <div
                    class="w-full rounded-lg border bg-card p-6 text-card-foreground shadow-lg md:p-8"
                >
                    <div v-if="title || description" class="mb-6 space-y-2 text-center">
                        <h1 v-if="title" class="text-2xl font-bold tracking-tight">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
