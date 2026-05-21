export const paddleConfig = {
    get invoicesUrl(): string {
        if (import.meta.env.VITE_PADDLE_ENV === 'sandbox') {
            return 'https://sandbox-vendors.paddle.com/invoices';
        }

        return 'https://vendors.paddle.com/invoices';
    },

    invoiceUrl(transactionId: string): string {
        if (import.meta.env.VITE_PADDLE_ENV === 'sandbox') {
            return `https://sandbox-vendors.paddle.com/invoice/${transactionId}`;
        }

        return `https://vendors.paddle.com/invoice/${transactionId}`;
    },
};
