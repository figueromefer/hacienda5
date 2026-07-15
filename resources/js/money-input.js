export default function moneyInput(initialValue = '') {
    const clean = value => String(value ?? '')
        .replace(/[$,\\s]/g, '')
        .replace(/[^0-9.-]/g, '');

    return {
        display: String(initialValue ?? ''),

        init() {
            this.format();
        },

        unformat() {
            this.display = clean(this.display);
        },

        format() {
            const normalized = clean(this.display);

            if (normalized === '' || normalized === '-' || Number.isNaN(Number(normalized))) {
                this.display = normalized;
                return;
            }

            this.display = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(normalized));
        },
    };
}

