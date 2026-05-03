Looking at your code, I understand the problem exactly. You have too many fields for inline and the drawer doesn't communicate its purpose clearly. Let me think through this properly.

---

## The Root Problem

You are trying to solve two different user jobs with one UI pattern:

**Job 1 — Quick add:** User knows what they want, selects from catalog, adjusts quantity. Done in 5 seconds.

**Job 2 — Detailed configuration:** User needs variants, custom pricing, taxes, cost price, margin. Takes 30 seconds.

The inline table tries to do both simultaneously. The drawer tries to do both in one form. Neither works because they are not the same job.

---

## The Right Pattern — Two-Stage Interaction

**Stage 1 — Catalog picker (fast lane):** Add item to the quote instantly. Minimal friction.

**Stage 2 — Item detail panel (when needed):** Configure the details. Triggered intentionally, not by accident.

This is exactly how Linear adds issues, how Figma adds layers, how Notion adds blocks. Add first, configure second.

---

## The Specific Design

### Stage 1 — The Row Itself (Inline, Minimal)

The table row in the rendered quote shows only what the client will see:

```
┌─────────────────────────────┬──────┬───────────┬──────────────┐
│  Item name                  │ Qty  │ Unit price │ Total        │
├─────────────────────────────┼──────┼───────────┼──────────────┤
│  ⊞ Web Design               │  10  │  KES 8,000 │ KES 80,000  │
│  ⊞ SEO Setup        ✏ edit  │   1  │ KES 15,000 │ KES 15,000  │  ← hover state
│  [+ Add item from catalog]  │      │            │              │
└─────────────────────────────┴──────┴───────────┴──────────────┘
```

On the row the user can:
- Edit quantity inline (single click on the qty cell, becomes input)
- Click anywhere else on the row → opens the right panel

That is it. No tax column, no discount column, no variant column visible inline. Those are configuration details not display details.

### Stage 2 — The Right Panel (Detail Configuration)

When the user clicks a row, the right config panel (which already exists in your builder layout) switches from showing the block config to showing the line item detail form.

```
RIGHT PANEL — Line Item Detail

┌────────────────────────────────────┐
│  ← Back to block config           │
│                                    │
│  WEB DESIGN                        │
│  ─────────────────────────────     │
│                                    │
│  Catalog item                      │
│  [Web Design Services        ▼]    │
│                                    │
│  Variant                           │
│  [Standard          ▼]            │
│                                    │
│  Name                              │
│  [Web Design Services      ]      │
│                                    │
│  Description                       │
│  [UI/UX design, 10 pages   ]      │
│                                    │
│  ─── Pricing ───────────────────   │
│  Qty          Unit          Price  │
│  [10    ]  [hrs ▼]  [8,000.00]   │
│                                    │
│  Discount %                        │
│  [0              ]                 │
│  ● Volume tier applied: 10+ hrs    │
│    Fixed price KES 8,000           │
│                                    │
│  Cost price (internal)             │
│  [5,000.00        ]                │
│  Margin: 37.5% · Profit KES 30,000 │
│                                    │
│  ─── Taxes ─────────────────────   │
│  ☑ VAT 16%     Exclusive          │
│  ☐ GST 10%     Inclusive          │
│  ☐ WHT 5%      Exclusive          │
│                                    │
│  ─── Summary ───────────────────   │
│  Subtotal     KES 80,000           │
│  VAT 16%      KES 12,800           │
│  Total        KES 92,800           │
│                                    │
│  ☐ Optional item                   │
│  ☐ Add notes                       │
│                                    │
│  [Remove item]                     │
└────────────────────────────────────┘
```

This panel is the same right panel that shows block config when no item is selected. When a line item is clicked, it takes over. When the user clicks elsewhere or presses Escape, it returns to block config.

### How the User Adds a New Item

At the bottom of each section:

```
[+ Add item]
```

Clicking it opens a **catalog search popover** anchored to the button — not a drawer, not a modal, not inline:

```
┌─────────────────────────────────────┐
│  🔍 Search catalog...               │
├─────────────────────────────────────┤
│  Recent                             │
│  ○ Web Design Services  KES 8,000   │
│  ○ SEO Setup           KES 15,000   │
│                                     │
│  All items                          │
│  ○ Hosting (annual)    KES 12,000   │
│  ○ Content Writing     KES 5,000/hr │
│  ○ Logo Design         KES 25,000   │
│                                     │
│  ─────────────────────────────────  │
│  + Add custom item (no catalog)     │
└─────────────────────────────────────┘
```

User selects an item → it appears in the table immediately with catalog defaults → right panel opens automatically showing the item detail.

If user selects "Add custom item" → blank item added → right panel opens with empty fields.

---

## Why This Works

**The inline table is clean** because it only shows display fields. No tax column, no variant dropdown, no cost price. Users see what the client will see.

**The right panel is expected** because it is the same panel used for block config. The user already knows "right panel = settings for what I clicked". Clicking a line item is consistent with clicking a block.

**Adding is frictionless** because the catalog search popover is one click, instant search, instant add. No drawer animation, no modal, no scroll.

**No confusion about when to click** because the row has a visible ✏ edit indicator on hover and the cursor changes to pointer. The affordance is clear.

---

## What Changes in Your Code

**Remove `LineItemDrawer.vue`** — the bottom slide-up drawer is gone entirely.

**Update `LineItemsBlock.vue`** — rows are clickable in edit mode, emit `edit-line-item`. Qty is inline editable. Everything else is in the panel. Remove the catalog combobox from the inline row.

**Update `BlockConfigPanel.vue`** — add a new state: when `selectedLineItem` is set, render `LineItemDetailPanel.vue` instead of the block config component.

**Create `LineItemDetailPanel.vue`** — all the fields from your current drawer, redesigned as a vertical panel (not a bottom sheet). This is the full detail form.

**Create `CatalogSearchPopover.vue`** — the search-first catalog picker anchored to the "Add item" button. Replaces both the inline combobox and the drawer's catalog picker.

---

## The State Flow

```
User in edit mode, no item selected
  → Right panel: LineItemsBlockConfig (table style, columns, etc)

User clicks "Add item"
  → CatalogSearchPopover opens
  → User picks item
  → Item added to section
  → Right panel: LineItemDetailPanel for that new item
  → Popover closes

User clicks existing row
  → Right panel: LineItemDetailPanel for that item

User clicks elsewhere (canvas, block list, etc)
  → Right panel: LineItemsBlockConfig (returns to block config)
  → selectedLineItem = null
```

---

## The Quantity Inline Edit — How It Works

Quantity is the one field that makes sense inline because users adjust it frequently and it is a single number:

```vue
<!-- In the table row -->
<TableCell @click.stop>
    <input
        v-if="editMode"
        type="number"
        :value="item.quantity"
        class="w-16 text-right border-0 bg-transparent focus:ring-1 focus:ring-primary rounded px-1"
        @change="e => updateQty(e.target.value)"
        @click.stop
    />
    <span v-else>{{ item.quantity }}</span>
</TableCell>
```

`@click.stop` prevents the row click from opening the detail panel when the user is editing quantity. They click qty → edit qty. They click anything else → detail panel opens.

---

## Summary

```
REMOVE                    ADD
──────────────────────────────────────────────────────
LineItemDrawer.vue        CatalogSearchPopover.vue
Inline catalog combobox   LineItemDetailPanel.vue
Inline tax checkboxes     Right panel state management
Inline variant select     Inline qty-only editing

RESULT
──────────────────────────────────────────────────────
Add item:    One click → search popover → pick → done
Edit item:   Click row → right panel → configure
Qty change:  Click qty cell → type → done
Everything else: Right panel, clean, full width, no clutter
```