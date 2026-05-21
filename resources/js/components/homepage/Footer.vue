<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
</script>

<template>
    <footer class="border-t border-border/40 bg-muted/20">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <Link :href="dashboard()" class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <img
                            src="/favicon.svg"
                            alt="QuoteSync"
                            class="h-4 w-4"
                        />
                        <span
                            class="text-lg font-semibold text-foreground"
                            style="font-family: var(--font-display)"
                        >
                            QuoteSync
                        </span>
                    </div>
                </Link>
                <p class="text-sm text-muted-foreground">
                    © 2026 QuoteSync. Professional quoting made simple.
                </p>
                <p class="text-sm text-muted-foreground">
                    Powered by EpochWeave
                </p>
            </div>
        </div>
    </footer>
</template>
