<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import GradientText from '@/components/ui/gradient-text.vue';
import Safari from '@/components/ui/safari.vue';
import {
    ArrowRight,
    BarChart3,
    Bell,
    Check,
    CheckCircle2,
    ChevronRight,
    Circle,
    ClipboardList,
    Clock,
    Eye,
    FileText,
    LayoutGrid,
    LayoutDashboard,
    MessageSquare,
    MoreHorizontal,
    RefreshCw,
    SendHorizonal,
    Settings,
    Shield,
    Tag,
    Users,
    XCircle,
    Zap,
} from 'lucide-vue-next';

interface Props {
    canRegister: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <section
        class="relative mx-auto flex min-h-screen max-w-7xl flex-col items-center justify-center px-6 pt-32 pb-20"
    >
        <!-- Text content -->
        <div class="text-center">
            <!-- Badge -->
            <div
                v-motion
                :initial="{ opacity: 0, y: -20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 600, delay: 200 },
                }"
                class="mb-8 inline-flex items-center gap-3 rounded-full border border-border bg-background/80 px-4 py-2 backdrop-blur-sm"
            >
                <div class="h-2 w-2 animate-pulse rounded-full bg-primary" />
                <span class="text-sm font-medium text-muted-foreground">
                    AI-powered quote generation is here
                </span>
            </div>

            <!-- Headline -->
            <h1
                v-motion
                :initial="{ opacity: 0, y: 30 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 300 },
                }"
                class="mb-6 text-5xl leading-[1.1] font-bold tracking-tight text-foreground md:text-7xl lg:text-8xl"
                style="
                    font-family: var(--font-display);
                    letter-spacing: -0.04em;
                "
            >
                Send smarter.<br />
                Close <GradientText>faster.</GradientText><br />
                Get paid.
            </h1>

            <!-- Subheadline -->
            <p
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 400 },
                }"
                class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-muted-foreground md:text-xl"
            >
                Professional quotes, e-signatures, and invoices — built for
                modern businesses worldwide. From the first draft to final
                payment, all in one place.
            </p>

            <!-- CTA Buttons -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 500 },
                }"
                class="mb-8 flex flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="group inline-flex items-center gap-2 rounded-full border border-primary bg-primary px-8 py-4 text-base font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/30"
                >
                    Start for free — no card needed
                    <ArrowRight
                        class="h-4 w-4 transition-transform group-hover:translate-x-1"
                    />
                </Link>
                <Link
                    :href="login()"
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-8 py-4 text-base font-medium text-foreground transition-all hover:bg-accent hover:shadow-md"
                >
                    Sign in
                </Link>
            </div>

            <!-- Trust Signals -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 600 },
                }"
                class="flex flex-wrap items-center justify-center gap-6 text-sm text-muted-foreground"
            >
                <div class="flex items-center gap-2">
                    <Check class="h-4 w-4 text-primary" />
                    <span>No credit card required</span>
                </div>
                <div class="flex items-center gap-2">
                    <Check class="h-4 w-4 text-primary" />
                    <span>Setup in under 5 minutes</span>
                </div>
                <div class="flex items-center gap-2">
                    <Check class="h-4 w-4 text-primary" />
                    <span>Cancel anytime</span>
                </div>
            </div>
        </div>

        <!-- Safari mockup -->
        <div
            v-motion
            :initial="{ opacity: 0, y: 40, scale: 0.97 }"
            :enter="{
                opacity: 1,
                y: 0,
                scale: 1,
                transition: { duration: 900, delay: 700 },
            }"
            class="mt-20 w-full max-w-[1100px]"
        >
            <Safari url="app.quotesync.com/quotes/42" class="w-full">
                <!-- ── App shell ─────────────────────────────────────────────── -->
                <div
                    class="flex h-full overflow-hidden bg-background text-foreground"
                    style="min-height: 540px; font-size: 11px"
                >
                    <!-- ── Sidebar ──────────────────────────────────────────── -->
                    <aside
                        class="flex w-[190px] shrink-0 flex-col border-r border-border bg-muted/20 p-3"
                    >
                        <!-- Brand -->
                        <div class="mb-4 flex items-center gap-2.5 px-1">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/15"
                            >
                                <img
                                    src="/favicon.svg"
                                    alt="QuoteSync"
                                    class="h-4 w-4"
                                />
                            </div>
                            <div>
                                <div
                                    class="text-[11px] leading-none font-bold text-foreground"
                                >
                                    QuoteSync
                                </div>
                                <div
                                    class="mt-1 text-[9px] text-muted-foreground"
                                >
                                    Powered By EpochWeave
                                </div>
                            </div>
                        </div>

                        <!-- Nav items -->
                        <nav class="space-y-0.5">
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <LayoutDashboard class="h-3 w-3" />Dashboard
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <Users class="h-3 w-3" />Clients
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <Tag class="h-3 w-3" />Catalog
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md bg-primary/10 px-2 py-1.5 text-[10px] font-semibold text-primary"
                            >
                                <FileText class="h-3 w-3" />Quotes
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <ClipboardList class="h-3 w-3" />Invoices
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <FileText class="h-3 w-3" />Credit Notes
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <ClipboardList class="h-3 w-3" />Tasks
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <BarChart3 class="h-3 w-3" />Analytics
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <Shield class="h-3 w-3" />Approvals
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <Settings class="h-3 w-3" />Configuration
                            </div>
                        </nav>

                        <!-- Bottom user -->
                        <div
                            class="mt-auto space-y-0.5 border-t border-border pt-2"
                        >
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <Settings class="h-3 w-3" />Business setup
                            </div>
                            <div
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10px] text-muted-foreground"
                            >
                                <Users class="h-3 w-3" />Teams
                            </div>
                            <div
                                class="mt-1 flex items-center gap-2 rounded-md px-2 py-1.5"
                            >
                                <div
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[8px] font-bold text-primary-foreground"
                                >
                                    VN
                                </div>
                                <span class="text-[10px] font-medium"
                                    >Vincent Ndegwa</span
                                >
                            </div>
                        </div>
                    </aside>

                    <!-- ── Main area ────────────────────────────────────────── -->
                    <div class="flex flex-1 flex-col overflow-hidden">
                        <!-- Top bar breadcrumb -->
                        <div
                            class="flex h-9 shrink-0 items-center justify-between border-b border-border px-4"
                        >
                            <div
                                class="flex items-center gap-1 text-[10px] text-muted-foreground"
                            >
                                <span>Quotes</span>
                                <ChevronRight class="h-2.5 w-2.5" />
                                <span class="font-medium text-foreground"
                                    >Meridian Branding Proposal</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="relative">
                                    <Bell
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                    <div
                                        class="absolute -top-0.5 -right-0.5 h-1.5 w-1.5 rounded-full bg-primary"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4" >
                            <!-- Page header -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <h1
                                        class="text-sm leading-tight font-bold text-foreground"
                                    >
                                        Meridian Branding Proposal
                                    </h1>
                                    <p
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        QS-2026-042
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <!-- Status badge -->
                                    <span
                                        class="inline-flex items-center rounded-full border border-primary/30 bg-primary/10 px-2 py-0.5 text-[9px] font-semibold text-primary"
                                    >
                                        Viewed
                                    </span>
                                    <!-- Actions -->
                                    <button
                                        class="flex items-center gap-1 rounded-md border border-border px-2 py-0.5 text-[9px] text-muted-foreground"
                                    >
                                        <MessageSquare class="h-2.5 w-2.5" />
                                        Follow-ups (2)
                                    </button>
                                    <button
                                        class="flex items-center gap-1 rounded-md border border-border px-2 py-0.5 text-[9px] text-muted-foreground"
                                    >
                                        <RefreshCw class="h-2.5 w-2.5" />
                                        Resend
                                    </button>
                                    <button
                                        class="flex items-center gap-1 rounded-md border border-border px-2 py-0.5 text-[9px] text-muted-foreground"
                                    >
                                        <ClipboardList class="h-2.5 w-2.5" />
                                        Create task
                                    </button>
                                    <button
                                        class="rounded-md border border-border p-0.5"
                                    >
                                        <MoreHorizontal
                                            class="h-3 w-3 text-muted-foreground"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Content grid -->
                            <div class="flex flex-1 overflow-hidden">
                                <!-- ── Document column ──────────────────────── -->
                                <div
                                    class="flex flex-1 flex-col gap-3 overflow-y-auto"
                                >
                                    <!-- Meta bar -->
                                    <div
                                        class="flex mt-2 items-center gap-4 rounded-sm border border-border bg-muted/20 px-3 py-2 text-[10px]"
                                    >
                                        <div>
                                            <span class="text-muted-foreground"
                                                >Client </span
                                            ><span class="font-semibold"
                                                >Meridian Studio</span
                                            >
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground"
                                                >Total </span
                                            ><span class="font-semibold"
                                                >$14,700.00</span
                                            >
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground"
                                                >Valid until </span
                                            ><span class="font-semibold"
                                                >Jun 09, 2026</span
                                            >
                                        </div>
                                        <div>
                                            <span class="text-muted-foreground"
                                                >Sent </span
                                            ><span class="font-semibold"
                                                >May 10, 2026</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Quote document -->
                                    <div
                                        class="overflow-hidden rounded-sm border border-border bg-white shadow-sm"
                                    >
                                        <!-- Header block -->
                                        <div
                                            class="flex items-start justify-between border-b border-border px-5 py-4"
                                        >
                                            <div
                                                class="text-sm font-bold text-primary"
                                                style="
                                                    font-family: var(
                                                        --font-display
                                                    );
                                                "
                                            >
                                                Hartwell &amp; Co.
                                            </div>
                                            <div class="text-right">
                                                <div
                                                    class="text-[11px] font-semibold text-foreground"
                                                >
                                                    QS-2026-042
                                                </div>
                                                <div
                                                    class="text-[9px] mt-1 text-muted-foreground"
                                                >
                                                    Issued: May 10, 2026
                                                </div>
                                                <div
                                                    class="text-[9px] mt-1 text-muted-foreground"
                                                >
                                                    Valid until: Jun 09, 2026
                                                </div>
                                            </div>
                                        </div>

                                        <!-- From / To block -->
                                        <div
                                            class="grid grid-cols-2 gap-4 border-b border-border px-5 py-3"
                                        >
                                            <div>
                                                <div
                                                    class="mb-0.5 text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    FROM
                                                </div>
                                                <div
                                                    class="text-[10px] font-semibold text-foreground"
                                                >
                                                    Hartwell &amp; Co.
                                                </div>
                                                <div
                                                    class="text-[9px] text-muted-foreground"
                                                >
                                                    London, United Kingdom
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div
                                                    class="mb-0.5 text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    TO
                                                </div>
                                                <div
                                                    class="text-[10px] font-semibold text-foreground"
                                                >
                                                    Meridian Studio
                                                </div>
                                                <div
                                                    class="text-[9px] text-muted-foreground"
                                                >
                                                    New York, United States
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cover message placeholder -->
                                        <div
                                            class="border-b border-border px-5 py-3"
                                        >
                                            <p
                                                class="text-[9px] text-muted-foreground/60 italic"
                                            >
                                                Write a personal intro message
                                                for your client...
                                            </p>
                                        </div>

                                        <!-- Section heading -->
                                        <div class="px-5 pt-3 pb-1">
                                            <h4
                                                class="text-[10px] font-semibold text-primary"
                                            >
                                                Creative Services
                                            </h4>
                                        </div>

                                        <!-- Line items table -->
                                        <div class="px-5 pb-3">
                                            <table
                                                class="w-full border-collapse"
                                            >
                                                <thead>
                                                    <tr
                                                        class="border-b border-border"
                                                    >
                                                        <th
                                                            class="py-1.5 text-left text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                        >
                                                            Item
                                                        </th>
                                                        <th
                                                            class="py-1.5 text-right text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                        >
                                                            Qty
                                                        </th>
                                                        <th
                                                            class="py-1.5 text-right text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                        >
                                                            Unit
                                                        </th>
                                                        <th
                                                            class="py-1.5 text-right text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                        >
                                                            Disc
                                                        </th>
                                                        <th
                                                            class="py-1.5 text-right text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                        >
                                                            Tax
                                                        </th>
                                                        <th
                                                            class="py-1.5 text-right text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                        >
                                                            Total
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        class="border-b border-border"
                                                    >
                                                        <td class="py-2">
                                                            <div
                                                                class="text-[10px] font-medium text-foreground"
                                                            >
                                                                Brand Identity
                                                                Package
                                                            </div>
                                                            <div
                                                                class="text-[8px] text-muted-foreground"
                                                            >
                                                                Logo, guidelines
                                                                &amp; brand
                                                                assets
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            1 lot
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            $8,500
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            0.00%
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            $850
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px] font-bold"
                                                        >
                                                            $9,350
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="py-2">
                                                            <div
                                                                class="text-[10px] font-medium text-foreground"
                                                            >
                                                                Website Design —
                                                                8 pages
                                                            </div>
                                                            <div
                                                                class="text-[8px] text-muted-foreground"
                                                            >
                                                                Figma,
                                                                responsive, 2
                                                                revisions
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            1 lot
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            $6,200
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            0.00%
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px]"
                                                        >
                                                            $620
                                                        </td>
                                                        <td
                                                            class="py-2 text-right text-[9px] font-bold"
                                                        >
                                                            $6,820
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Totals -->
                                            <div
                                                class="mt-3 space-y-1 border-t border-border pt-2"
                                            >
                                                <div
                                                    class="flex justify-between text-[9px] text-muted-foreground"
                                                >
                                                    <span>Subtotal</span>
                                                    <span>$14,700.00</span>
                                                </div>
                                                <div
                                                    class="flex justify-between text-[9px] text-muted-foreground"
                                                >
                                                    <span>VAT (10%)</span>
                                                    <span>$1,470.00</span>
                                                </div>
                                                <div
                                                    class="flex justify-between text-[10px] font-bold text-primary"
                                                >
                                                    <span>Total</span>
                                                    <span>$16,170.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── Right stats panel ────────────────────── -->
                                <div
                                    class="w-[220px] shrink-0 space-y-3 overflow-y-auto bg-background p-3"
                                >
                                    <!-- Quote Stats card -->
                                    <div
                                        class="rounded-sm border border-border bg-card p-3"
                                    >
                                        <div
                                            class="mb-2.5 flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[10px] font-semibold text-foreground"
                                                >Quote Stats</span
                                            >
                                            <!-- Hot lead badge -->
                                            <span
                                                class="inline-flex items-center gap-0.5 rounded-full bg-orange-100 px-1.5 py-0.5 text-[8px] font-semibold text-orange-600"
                                            >
                                                🔥 Hot lead
                                            </span>
                                        </div>

                                        <!-- Hero stat: opened count -->
                                        <div
                                            class="mb-2 rounded-md bg-muted/40 px-2.5 py-2"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <div
                                                    class="flex items-center gap-1 text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    <Eye class="h-2.5 w-2.5" />
                                                    Opened
                                                </div>
                                                <span
                                                    class="text-lg leading-none font-bold text-orange-500"
                                                    >6×</span
                                                >
                                            </div>
                                            <div
                                                class="mt-0.5 text-[8px] text-muted-foreground"
                                            >
                                                <Clock class="inline h-2 w-2" />
                                                8 min 24 sec total reading time
                                            </div>
                                            <div
                                                class="text-[8px] text-muted-foreground"
                                            >
                                                Last opened 14 min ago
                                            </div>
                                        </div>

                                        <!-- Secondary stats -->
                                        <div
                                            class="mb-2 grid grid-cols-2 gap-1.5"
                                        >
                                            <div>
                                                <div
                                                    class="text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    Valid until
                                                </div>
                                                <div
                                                    class="text-[9px] font-medium"
                                                >
                                                    Jun 09, 2026
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    Sent
                                                </div>
                                                <div
                                                    class="text-[9px] font-medium"
                                                >
                                                    May 10, 2026
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    Status
                                                </div>
                                                <div
                                                    class="text-[9px] font-medium capitalize"
                                                >
                                                    Viewed
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                >
                                                    Owner
                                                </div>
                                                <div
                                                    class="text-[9px] font-medium"
                                                >
                                                    J. Hartwell
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Win probability -->
                                        <div class="mb-2">
                                            <div
                                                class="mb-1 flex items-center justify-between"
                                            >
                                                <span
                                                    class="text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                                    >Win Probability</span
                                                >
                                                <span
                                                    class="text-[9px] font-bold text-green-600"
                                                    >78%</span
                                                >
                                            </div>
                                            <div
                                                class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                            >
                                                <div
                                                    class="h-full rounded-full bg-green-500 transition-all"
                                                    style="width: 78%"
                                                ></div>
                                            </div>
                                            <div
                                                class="mt-0.5 text-[8px] text-muted-foreground"
                                            >
                                                High confidence · 3 signals
                                            </div>
                                        </div>

                                        <!-- Divider -->
                                        <div
                                            class="my-2 border-t border-border"
                                        ></div>

                                        <!-- Progress timeline -->
                                        <div
                                            class="mb-0.5 text-[8px] font-semibold tracking-wider text-muted-foreground uppercase"
                                        >
                                            Progress
                                        </div>
                                        <div class="space-y-1.5">
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600"
                                                >
                                                    <FileText class="h-2 w-2" />
                                                </div>
                                                <span
                                                    class="flex-1 text-[9px] font-medium"
                                                    >Created</span
                                                >
                                                <span
                                                    class="text-[8px] text-muted-foreground"
                                                    >May 10</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600"
                                                >
                                                    <SendHorizonal
                                                        class="h-2 w-2"
                                                    />
                                                </div>
                                                <span
                                                    class="flex-1 text-[9px] font-medium"
                                                    >Sent</span
                                                >
                                                <span
                                                    class="text-[8px] text-muted-foreground"
                                                    >May 10</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-600"
                                                >
                                                    <Eye class="h-2 w-2" />
                                                </div>
                                                <span
                                                    class="flex-1 text-[9px] font-medium text-violet-600"
                                                    >First viewed</span
                                                >
                                                <span
                                                    class="text-[8px] text-muted-foreground"
                                                    >May 10</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                                                >
                                                    <CheckCircle2
                                                        class="h-2 w-2"
                                                    />
                                                </div>
                                                <span
                                                    class="flex-1 text-[9px] text-muted-foreground/50"
                                                    >Accepted</span
                                                >
                                                <span
                                                    class="text-[8px] text-muted-foreground/40"
                                                    >—</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <div
                                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                                                >
                                                    <XCircle class="h-2 w-2" />
                                                </div>
                                                <span
                                                    class="flex-1 text-[9px] text-muted-foreground/50"
                                                    >Declined</span
                                                >
                                                <span
                                                    class="text-[8px] text-muted-foreground/40"
                                                    >—</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Invoices card -->
                                    <div
                                        class="rounded-lg border border-border bg-card p-3"
                                    >
                                        <div
                                            class="mb-2 flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[10px] font-semibold text-foreground"
                                                >Invoices</span
                                            >
                                            <span
                                                class="text-[8px] text-muted-foreground"
                                                >0</span
                                            >
                                        </div>
                                        <div
                                            class="rounded-md border border-dashed border-border py-3 text-center"
                                        >
                                            <ClipboardList
                                                class="mx-auto mb-1 h-4 w-4 text-muted-foreground/30"
                                            />
                                            <p
                                                class="text-[9px] text-muted-foreground"
                                            >
                                                Invoices are created once the
                                                quote is won.
                                            </p>
                                        </div>
                                        <p
                                            class="mt-2 text-[9px] text-muted-foreground"
                                        >
                                            Track invoices generated from this
                                            quote
                                        </p>
                                    </div>

                                    <!-- Activity card -->
                                    <div
                                        class="rounded-lg border border-border bg-card p-3"
                                    >
                                        <div
                                            class="mb-2 text-[10px] font-semibold text-foreground"
                                        >
                                            Activity
                                        </div>
                                        <div class="space-y-2">
                                            <div
                                                class="flex items-start gap-1.5"
                                            >
                                                <div
                                                    class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-600"
                                                >
                                                    <Eye class="h-2 w-2" />
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-[9px] font-medium"
                                                    >
                                                        Quote opened 6×
                                                    </p>
                                                    <p
                                                        class="text-[8px] text-muted-foreground"
                                                    >
                                                        Most recent 14 min ago
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-start gap-1.5"
                                            >
                                                <div
                                                    class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600"
                                                >
                                                    <SendHorizonal
                                                        class="h-2 w-2"
                                                    />
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-[9px] font-medium"
                                                    >
                                                        Quote sent to client
                                                    </p>
                                                    <p
                                                        class="text-[8px] text-muted-foreground"
                                                    >
                                                        May 10 · J. Hartwell
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-start gap-1.5"
                                            >
                                                <div
                                                    class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600"
                                                >
                                                    <FileText class="h-2 w-2" />
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-[9px] font-medium"
                                                    >
                                                        Quote created
                                                    </p>
                                                    <p
                                                        class="text-[8px] text-muted-foreground"
                                                    >
                                                        May 10 · J. Hartwell
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Safari>
        </div>
    </section>
</template>
