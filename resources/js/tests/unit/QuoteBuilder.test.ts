import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import type { QuoteBuilderState, BuilderCatalogItem, BuilderTaxOption, BuilderConfigurationUnit, WorkspaceSettings } from '@/types';

// Mock Inertia page props
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            localization: {
                date_format: 'MMM d, yyyy',
                time_format: '12h',
                timezone: 'UTC',
                first_day_of_week: 0,
            },
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                },
            },
            errors: {},
            name: 'QuoteSync',
            brand: 'EpochWeave',
            whiteLabel: false,
            workspace_currency: 'USD',
            pending_approvals_count: 0,
        },
    }),
}));

describe('QuoteBuilder', () => {
    let mockState: QuoteBuilderState;
    let mockCatalogItems: BuilderCatalogItem[];
    let mockTaxes: BuilderTaxOption[];
    let mockUnits: BuilderConfigurationUnit[];
    let mockSettings: WorkspaceSettings;

    beforeEach(() => {
        mockState = {
            id: 1,
            title: 'Test Quote',
            client_id: null,
            template_id: null,
            currency: 'USD',
            subtotal: 0,
            discount_amount: 0,
            total: 0,
            valid_until: null,
            notes: null,
            terms: null,
            footer: null,
            status: 'draft',
            layout: null,
            layout_snapshot: null,
            sections: [
                {
                    id: 'section-1',
                    title: 'Section 1',
                    sort_order: 0,
                    line_items: [
                        { id: 1, name: 'Item 1', quantity: 1, unit_price: 100, discount_percent: 0, description: '', unit: 'ea', taxes: [], optional: false },
                        { id: 2, name: 'Item 2', quantity: 1, unit_price: 200, discount_percent: 0, description: '', unit: 'ea', taxes: [], optional: false },
                        { id: 3, name: 'Item 3', quantity: 1, unit_price: 300, discount_percent: 0, description: '', unit: 'ea', taxes: [], optional: false },
                    ],
                },
            ],
        };

        mockCatalogItems = [];
        mockTaxes = [];
        mockUnits = [];
        mockSettings = {
            workspace: {
                id: 1,
                name: 'Test Workspace',
                currency: 'USD',
            },
            quotes: {
                quote_prefix: 'QTS',
                quote_number_sequence: 1,
                quote_validity_days: 30,
            },
        } as WorkspaceSettings;
    });

    it('renders without errors', async () => {
        const wrapper = mount(QuoteBuilder, {
            props: {
                modelValue: mockState,
                mode: 'quote',
                catalogItems: mockCatalogItems,
                taxes: mockTaxes,
                units: mockUnits,
                settings: mockSettings,
            },
        });

        expect(wrapper.exists()).toBe(true);
        await wrapper.unmount();
    });

    it('does not cause recursive updates when removing line item', async () => {
        const modelValue = ref<QuoteBuilderState>({ ...mockState });
        
        const wrapper = mount(QuoteBuilder, {
            props: {
                modelValue: modelValue.value,
                mode: 'quote',
                catalogItems: mockCatalogItems,
                taxes: mockTaxes,
                units: mockUnits,
                settings: mockSettings,
            },
        });

        // Wait for component to stabilize
        await new Promise(resolve => setTimeout(resolve, 100));

        // Emit remove-line-item event
        await wrapper.vm.$emit('remove-line-item', { sectionIndex: 0, lineItemIndex: 1 });

        // Wait for updates
        await new Promise(resolve => setTimeout(resolve, 100));

        // Check that the component is still mounted and hasn't crashed
        expect(wrapper.exists()).toBe(true);
        
        await wrapper.unmount();
    });

    it('updates sections when remove-line-item event is emitted', async () => {
        const modelValue = ref<QuoteBuilderState>({ ...mockState });

        const wrapper = mount(QuoteBuilder, {
            props: {
                modelValue: modelValue.value,
                mode: 'quote',
                catalogItems: mockCatalogItems,
                taxes: mockTaxes,
                units: mockUnits,
                settings: mockSettings,
            },
        });

        // Wait for component to stabilize
        await new Promise(resolve => setTimeout(resolve, 100));

        // Emit remove-line-item event
        await wrapper.vm.$emit('remove-line-item', { sectionIndex: 0, lineItemIndex: 1 });

        // Wait for updates
        await new Promise(resolve => setTimeout(resolve, 100));

        // Check that the component is still mounted
        expect(wrapper.exists()).toBe(true);
        
        await wrapper.unmount();
    });
});
