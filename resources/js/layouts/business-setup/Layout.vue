<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem, WorkspaceSettingsGroupSummary } from '@/types';

type SharedProps = {
    groups?: WorkspaceSettingsGroupSummary[];
};

const page = usePage<SharedProps>();

const groupItems = (page.props.groups ?? []).map((group) => ({
    title: group.label,
    href: `/business-setup/${group.key}`,
}));

const sidebarNavItems: NavItem[] = [...groupItems];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Business setup"
            description="Configure organization defaults, policies, and operational preferences"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-56">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Business setup"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-2xl space-y-8">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
