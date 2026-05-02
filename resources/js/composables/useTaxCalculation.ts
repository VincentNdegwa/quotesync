export type TaxItem = {
    tax_rate: number;
    inclusive: boolean;
};

export const calculateLineItemTotals = (
    quantity: number,
    unitPrice: number,
    discountPercent: number,
    taxes: TaxItem[],
): {
    subtotal: number;
    taxAmount: number;
    total: number;
} => {

    console.log({
        "quonatity": quantity,
        "unitPrice": unitPrice,
        "discount": discountPercent,
        "taxes": taxes
    });
    
    const qty = Math.max(quantity, 0);
    const price = Math.max(unitPrice, 0);
    const discount = Math.min(Math.max(discountPercent, 0), 100);

    // This is the 'Stated Price' (e.g., 200)
    const baseAmount = qty * price * (1 - discount / 100);

    // 1. Calculate Inclusive Taxes (Extracted from the baseAmount)
    const inclusiveTaxAmount = taxes
        .filter((tax) => tax.inclusive)
        .reduce((sum, tax) => {
            const rate = Math.max(tax.tax_rate, 0);
            return sum + (baseAmount * rate) / (100 + rate);
        }, 0);

    // 2. Net price after extracting inclusive taxes
    const netPrice = baseAmount - inclusiveTaxAmount;

    // 3. Calculate Exclusive Taxes (Applied to net price, not baseAmount)
    const exclusiveTaxAmount = taxes
        .filter((tax) => !tax.inclusive)
        .reduce((sum, tax) => {
            const rate = Math.max(tax.tax_rate, 0);
            return sum + (netPrice * rate) / 100;
        }, 0);

    // 4. Final Calculations
    // Total Tax is the sum of both
    const taxAmount = inclusiveTaxAmount + exclusiveTaxAmount;

    // Total is Stated Price + Exclusive Taxes
    const total = baseAmount + exclusiveTaxAmount;

    // Subtotal is net price (baseAmount - inclusive taxes)
    const subtotal = netPrice;

    return {
        subtotal,
        taxAmount,
        total,
    };
};