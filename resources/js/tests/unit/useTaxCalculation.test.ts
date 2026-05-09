import { describe, it, expect } from 'vitest';
import { calculateLineItemTotals } from '../../composables/useTaxCalculation';

describe('useTaxCalculation', () => {
    it('calculates inclusive and exclusive taxes correctly from stated price', () => {
        const quantity = 1;
        const unitPrice = 200;
        const discountPercent = 0;
        const taxes = [
            { tax_rate: 10, inclusive: true },
            { tax_rate: 10, inclusive: false },
        ];

        const result = calculateLineItemTotals(
            quantity,
            unitPrice,
            discountPercent,
            taxes,
        );

        // Stated Price = 200
        // Inclusive Tax (10%) = 200 * 10 / 110 = 18.1818...
        // Exclusive Tax (10%) = 200 * 10 / 100 = 20
        // Total Tax = 18.18 + 20 = 38.18
        // Total = Stated Price + Exclusive Tax = 200 + 20 = 220

        expect(result.total).toBe(220);
        expect(result.taxAmount).toBeCloseTo(38.18, 2);
        expect(result.subtotal).toBeCloseTo(181.82, 2);
    });

    it('calculates only inclusive tax correctly', () => {
        const result = calculateLineItemTotals(1, 100, 0, [
            { tax_rate: 20, inclusive: true },
        ]);

        // 100 * 20 / 120 = 16.67
        expect(result.total).toBe(100);
        expect(result.taxAmount).toBeCloseTo(16.67, 2);
        expect(result.subtotal).toBeCloseTo(83.33, 2);
    });

    it('calculates only exclusive tax correctly', () => {
        const result = calculateLineItemTotals(1, 100, 0, [
            { tax_rate: 20, inclusive: false },
        ]);

        expect(result.total).toBe(120);
        expect(result.taxAmount).toBe(20);
        expect(result.subtotal).toBe(100);
    });
});
