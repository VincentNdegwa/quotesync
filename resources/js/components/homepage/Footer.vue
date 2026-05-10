<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { FileText } from 'lucide-vue-next';
</script>

<template>
    <footer class="border-t border-border/40 bg-muted/20">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <Link :href="dashboard()" class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-primary/20 bg-primary/10"
                    >
                        <FileText class="h-4 w-4 text-primary" />
                    </div>
                    <span
                        class="text-lg font-semibold text-foreground"
                        style="font-family: var(--font-display)"
                    >
                        QuoteSync
                    </span>
                </Link>
                <p class="text-sm text-muted-foreground">
                    © 2026 QuoteSync. Professional quoting made simple.
                </p>
            </div>
        </div>
    </footer>
</template>
