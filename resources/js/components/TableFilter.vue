<script setup lang="ts">
import { X, SlidersHorizontal, Search, ChevronDown, Check } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

// ─── Types ────────────────────────────────────────────────────────────────────

export type FilterOption = {
    value: string;
    label: string;
    count?: number;
    color?: string; // optional dot color e.g. 'bg-emerald-500'
};

export type FilterGroup = {
    key: string;
    label: string;
    type: 'select' | 'multi' | 'date_range';
    options?: FilterOption[];
    placeholder?: string;
};

export type ActiveFilters = Record<string, string | string[]>;

// ─── Props ────────────────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        groups: FilterGroup[];
        modelValue: ActiveFilters;
        search?: string;
        searchPlaceholder?: string;
        resultCount?: number | null;
    }>(),
    {
        search: '',
        searchPlaceholder: 'Search...',
        resultCount: null,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: ActiveFilters): void;
    (e: 'update:search', value: string): void;
}>();

// ─── State ────────────────────────────────────────────────────────────────────

const popoverOpen = ref(false);

// Local draft — only committed when user closes popover
const draft = ref<ActiveFilters>({ ...props.modelValue });

watch(() => props.modelValue, (val) => {
    draft.value = { ...val };
}, { deep: true });

// ─── Computed ─────────────────────────────────────────────────────────────────

const activeCount = computed(() => {
    return Object.values(props.modelValue).filter((v) => {
        if (Array.isArray(v)) return v.length > 0;
        return v !== '' && v !== null && v !== undefined;
    }).length;
});

// Active filter badges for display below the search bar
const activeBadges = computed(() => {
    const badges: Array<{ key: string; label: string; value: string; displayValue: string }> = [];

    for (const group of props.groups) {
        const val = props.modelValue[group.key];
        if (!val) continue;

        if (Array.isArray(val)) {
            for (const v of val) {
                const opt = group.options?.find(o => o.value === v);
                badges.push({
                    key:          group.key,
                    label:        group.label,
                    value:        v,
                    displayValue: opt?.label ?? v,
                });
            }
        } else if (val !== '') {
            if (group.type === 'date_range') {
                const parts = val.split('|');
                const from  = parts[0] ?? '';
                const to    = parts[1] ?? '';
                if (from || to) {
                    badges.push({
                        key:          group.key,
                        label:        group.label,
                        value:        val,
                        displayValue: from && to ? `${from} → ${to}` : from || to,
                    });
                }
            } else {
                const opt = group.options?.find(o => o.value === val);
                badges.push({
                    key:          group.key,
                    label:        group.label,
                    value:        val,
                    displayValue: opt?.label ?? val,
                });
            }
        }
    }

    return badges;
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

const getDraftValue = (key: string, type: FilterGroup['type']): string | string[] => {
    if (type === 'multi') {
        const v = draft.value[key];
        return Array.isArray(v) ? v : v ? [v] : [];
    }
    return (draft.value[key] as string) ?? '';
};

const isOptionActive = (key: string, value: string, type: FilterGroup['type']): boolean => {
    const v = draft.value[key];
    if (type === 'multi') {
        return Array.isArray(v) ? v.includes(value) : v === value;
    }
    return v === value;
};

const toggleOption = (key: string, value: string, type: FilterGroup['type']): void => {
    if (type === 'multi') {
        const current = (draft.value[key] as string[]) ?? [];
        draft.value = {
            ...draft.value,
            [key]: current.includes(value)
                ? current.filter(v => v !== value)
                : [...current, value],
        };
    } else {
        // toggle off if already selected
        draft.value = {
            ...draft.value,
            [key]: draft.value[key] === value ? '' : value,
        };
    }
};

const getDatePart = (key: string, part: 'from' | 'to'): string => {
    const v = (draft.value[key] as string) ?? '';
    const parts = v.split('|');
    return part === 'from' ? (parts[0] ?? '') : (parts[1] ?? '');
};

const setDatePart = (key: string, part: 'from' | 'to', value: string): void => {
    const current = (draft.value[key] as string) ?? '|';
    const parts   = current.split('|');
    const from    = parts[0] ?? '';
    const to      = parts[1] ?? '';
    draft.value   = {
        ...draft.value,
        [key]: part === 'from' ? `${value}|${to}` : `${from}|${value}`,
    };
};

const applyFilters = (): void => {
    emit('update:modelValue', { ...draft.value });
    popoverOpen.value = false;
};

const clearDraft = (): void => {
    const cleared: ActiveFilters = {};
    for (const group of props.groups) {
        cleared[group.key] = group.type === 'multi' ? [] : '';
    }
    draft.value = cleared;
};

const clearAll = (): void => {
    const cleared: ActiveFilters = {};
    for (const group of props.groups) {
        cleared[group.key] = group.type === 'multi' ? [] : '';
    }
    emit('update:modelValue', cleared);
};

const removeBadge = (key: string, value: string, type: FilterGroup['type']): void => {
    const current = props.modelValue[key];
    if (type === 'multi' && Array.isArray(current)) {
        emit('update:modelValue', {
            ...props.modelValue,
            [key]: current.filter(v => v !== value),
        });
    } else {
        emit('update:modelValue', { ...props.modelValue, [key]: '' });
    }
};

const onPopoverOpen = (open: boolean): void => {
    if (open) {
        // Sync draft from committed filters when opening
        draft.value = { ...props.modelValue };
    }
    popoverOpen.value = open;
};
</script>

<template>
    <div class="space-y-2">

        <!-- ── Search + filter trigger ─────────────────────────────────────── -->
        <div class="flex items-center gap-2">

            <!-- Search -->
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    :model-value="search"
                    :placeholder="searchPlaceholder"
                    class="pl-9 pr-4"
                    @update:model-value="emit('update:search', String($event))"
                />
                <button
                    v-if="search"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-muted-foreground/60 hover:text-foreground transition-colors"
                    type="button"
                    @click="emit('update:search', '')"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Filter trigger -->
            <Popover :open="popoverOpen" @update:open="onPopoverOpen">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        class="gap-2 whitespace-nowrap"
                        :class="activeCount > 0 ? 'border-primary text-primary' : ''"
                    >
                        <SlidersHorizontal class="h-4 w-4" />
                        Filters
                        <span
                            v-if="activeCount > 0"
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[11px] font-bold text-primary-foreground"
                        >
                            {{ activeCount }}
                        </span>
                        <ChevronDown
                            class="h-3.5 w-3.5 text-muted-foreground transition-transform duration-200"
                            :class="popoverOpen ? 'rotate-180' : ''"
                        />
                    </Button>
                </PopoverTrigger>

                <!-- Filter popover -->
                <PopoverContent
                    align="end"
                    class="w-80 p-0"
                >
                    <!-- Popover header -->
                    <div class="flex items-center justify-between border-b px-4 py-3">
                        <span class="text-sm font-semibold">Filters</span>
                        <button
                            type="button"
                            class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                            @click="clearDraft"
                        >
                            Clear all
                        </button>
                    </div>

                    <!-- Filter groups -->
                    <div class="max-h-[70vh] overflow-y-auto">
                        <div
                            v-for="(group, gi) in groups"
                            :key="group.key"
                        >
                            <div class="px-4 py-3">
                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    {{ group.label }}
                                </p>

                                <!-- Select / Multi — pill options -->
                                <template v-if="group.type === 'select' || group.type === 'multi'">
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            v-for="opt in group.options"
                                            :key="opt.value"
                                            type="button"
                                            class="flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-all"
                                            :class="isOptionActive(group.key, opt.value, group.type)
                                                ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                                                : 'border-muted-foreground/20 text-muted-foreground hover:border-muted-foreground/50 hover:text-foreground'"
                                            @click="toggleOption(group.key, opt.value, group.type)"
                                        >
                                            <!-- Status dot -->
                                            <span
                                                v-if="opt.color"
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="isOptionActive(group.key, opt.value, group.type)
                                                    ? 'bg-primary-foreground'
                                                    : opt.color"
                                            />

                                            <!-- Check for multi -->
                                            <Check
                                                v-else-if="group.type === 'multi' && isOptionActive(group.key, opt.value, group.type)"
                                                class="h-3 w-3"
                                            />

                                            {{ opt.label }}

                                            <!-- Count badge -->
                                            <span
                                                v-if="opt.count !== undefined"
                                                class="rounded-full px-1 text-[10px] font-bold"
                                                :class="isOptionActive(group.key, opt.value, group.type)
                                                    ? 'bg-primary-foreground/20 text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground'"
                                            >
                                                {{ opt.count }}
                                            </span>
                                        </button>
                                    </div>
                                </template>

                                <!-- Date range -->
                                <template v-else-if="group.type === 'date_range'">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="space-y-1">
                                            <label class="text-[10px] text-muted-foreground">From</label>
                                            <Input
                                                type="date"
                                                :model-value="getDatePart(group.key, 'from')"
                                                class="h-8 text-xs"
                                                @update:model-value="v => setDatePart(group.key, 'from', String(v))"
                                            />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] text-muted-foreground">To</label>
                                            <Input
                                                type="date"
                                                :model-value="getDatePart(group.key, 'to')"
                                                class="h-8 text-xs"
                                                @update:model-value="v => setDatePart(group.key, 'to', String(v))"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <Separator v-if="gi < groups.length - 1" />
                        </div>
                    </div>

                    <!-- Apply button -->
                    <div class="border-t p-3">
                        <Button class="w-full" size="sm" @click="applyFilters">
                            Apply filters
                        </Button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>

        <!-- ── Active filter badges ────────────────────────────────────────── -->
        <div
            v-if="activeBadges.length > 0"
            class="flex flex-wrap items-center gap-1.5"
        >
            <span class="text-xs text-muted-foreground">Filtered by:</span>

            <Badge
                v-for="badge in activeBadges"
                :key="`${badge.key}-${badge.value}`"
                variant="secondary"
                class="gap-1 pl-2 pr-1 text-xs font-medium"
            >
                <span class="text-muted-foreground">{{ badge.label }}:</span>
                {{ badge.displayValue }}
                <button
                    type="button"
                    class="ml-0.5 rounded-full p-0.5 hover:bg-muted-foreground/20 transition-colors"
                    @click="removeBadge(badge.key, badge.value, groups.find(g => g.key === badge.key)?.type ?? 'select')"
                >
                    <X class="h-2.5 w-2.5" />
                </button>
            </Badge>

            <button
                type="button"
                class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                @click="clearAll"
            >
                Clear all
            </button>
        </div>

        <!-- ── Result count ────────────────────────────────────────────────── -->
        <p
            v-if="resultCount !== null && (activeBadges.length > 0 || search)"
            class="text-xs text-muted-foreground"
        >
            {{ resultCount }} result{{ resultCount !== 1 ? 's' : '' }}
            <span v-if="search"> for "<strong>{{ search }}</strong>"</span>
        </p>
    </div>
</template>

<!-- 

You are not paranoid. The horizontal filter bar is the most common pattern and also the most problematic one. Let me explain why and then give you something better.

---

## Why Horizontal Filter Bars Fail

The horizontal bar works when you have 2-3 filters. Once you add search + status + date range + assignee + currency + sort — they either overflow, wrap awkwardly, or get hidden behind a "more filters" button that nobody clicks.

The other problem is visual weight. A row of dropdowns all at the same height with no hierarchy looks like a form, not a filter tool. The user has to read every label to understand what each control does.

---

## The Better Pattern — Search First, Filters Secondary

```
┌──────────────────────────────────────────────────────────────────┐
│  🔍 Search quotes, clients...              [Filters  ▼]  2 active│
└──────────────────────────────────────────────────────────────────┘
                                              ↓ when clicked
                                    ┌─────────────────────────┐
                                    │  Status                 │
                                    │  ○ All  ● Sent  ○ Won   │
                                    │                         │
                                    │  Date range             │
                                    │  [Apr 1] → [Apr 30]     │
                                    │                         │
                                    │  Assigned to            │
                                    │  [All team members ▼]   │
                                    │                         │
                                    │  [Clear filters]        │
                                    └─────────────────────────┘
```

Search is always visible and prominent. Filters live in a popover. Active filters show as removable badges below the search bar.

This pattern works because:
- Search is the primary action — 80% of users search, not filter
- Filters do not clutter the primary interface
- Active filter badges make the current state visible and easy to clear
- The filter count badge ("2 active") tells the user something is filtered without them having to open the popover

---

## The Component

This is a self-contained `TableFilters.vue` that you can drop into any index page:Now here is how you use it on any index page:

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TableFilters from '@/components/TableFilters.vue';
import type { FilterGroup, ActiveFilters } from '@/components/TableFilters.vue';

const search  = ref('');
const filters = ref<ActiveFilters>({
    status:    '',
    assigned:  '',
    date_sent: '',
    currency:  [],
});

// Define your filter groups once
const filterGroups: FilterGroup[] = [
    {
        key:   'status',
        label: 'Status',
        type:  'select',
        options: [
            { value: 'draft',    label: 'Draft',    color: 'bg-slate-400' },
            { value: 'sent',     label: 'Sent',     color: 'bg-blue-500'  },
            { value: 'viewed',   label: 'Viewed',   color: 'bg-violet-500'},
            { value: 'won',      label: 'Won',      color: 'bg-emerald-500'},
            { value: 'lost',     label: 'Lost',     color: 'bg-rose-500'  },
            { value: 'expired',  label: 'Expired',  color: 'bg-amber-400' },
        ],
    },
    {
        key:   'currency',
        label: 'Currency',
        type:  'multi',
        options: [
            { value: 'KES', label: 'KES' },
            { value: 'USD', label: 'USD' },
            { value: 'GBP', label: 'GBP' },
            { value: 'AED', label: 'AED' },
        ],
    },
    {
        key:   'date_sent',
        label: 'Date sent',
        type:  'date_range',
    },
    {
        key:   'assigned',
        label: 'Assigned to',
        type:  'select',
        options: teamMembers.value.map(u => ({
            value: String(u.id),
            label: u.name,
        })),
    },
];

// Push filters to URL when they change
watch([search, filters], () => {
    router.get(route('quotes.index'), {
        search:   search.value || undefined,
        status:   filters.value.status || undefined,
        currency: filters.value.currency?.length
            ? filters.value.currency
            : undefined,
        date_sent: filters.value.date_sent || undefined,
        assigned:  filters.value.assigned || undefined,
    }, {
        preserveState: true,
        replace:       true,
    });
}, { deep: true });
</script>

<template>
    <TableFilters
        v-model="filters"
        v-model:search="search"
        :groups="filterGroups"
        :result-count="quotes.total"
        search-placeholder="Search by quote title, number, or client..."
    />

    <!-- Your table below -->
</template>
```

---

## What Each Part Does

**Search bar** — full width, icon on left, clear button appears when typing. Prominent because it is the most used interaction.

**Filters button** — right-aligned, shows a count badge when filters are active, button border turns primary color to signal active state. Clicking opens the popover.

**Filter popover** — opens to the right. Each group renders based on its type. `select` allows one choice. `multi` allows many. `date_range` shows two date inputs. Options render as pill buttons not dropdowns — pills are easier to scan and tap.

**Apply button** — filters are drafted locally in the popover and only committed when the user clicks Apply. This prevents the table from refetching on every click while the user is still configuring.

**Active badges** — appear below the search bar showing exactly what is filtered. Each badge has an `×` to remove that specific filter individually. A "Clear all" link removes everything at once.

**Result count** — appears only when something is filtered or searched. Shows `4 results for "kettle"`. Disappears when no filter is active.

---

## The Design Decisions

**Popover not a sidebar drawer** — a sidebar drawer is for complex multi-step filtering like Figma or Notion. For a list view with 4-6 filters a popover is faster and less disruptive.

**Pills not dropdowns inside the popover** — dropdowns inside a popover create nested interaction layers that feel heavy. Pills let the user see all options immediately and toggle them without a click-to-open interaction.

**Draft pattern** — the popover has its own internal state. Changes are not applied until the user clicks Apply. This prevents the table from flickering on every option click while the user is still deciding. The Clear All inside the popover clears the draft only — not the committed filters. This matches the mental model of "I am configuring, then I apply."

**Badges are the persistent state indicator** — the user can close the popover and still see what is active. Individual badge removal is the fastest way to remove a single filter without re-opening the popover.

-->