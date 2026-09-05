import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['status', 'empty', 'items', 'total', 'checkout'];
    static values = { currentUrl: String, checkoutUrl: String };

    connect() {
        this.cartToken = window.localStorage.getItem('sr.cart.token') || '';
        this.refresh();
    }

    async refresh() {
        const response = await this.request(this.currentUrlValue, { method: 'GET' });
        if (!response.ok) {
            this.statusTarget.textContent = `Cart unavailable (${response.status})`;
            return;
        }

        const cart = await response.json();
        this.captureToken(response, cart);
        this.render(cart);
    }

    async checkout() {
        this.checkoutTarget.disabled = true;
        try {
            const response = await this.request(this.checkoutUrlValue, { method: 'POST' });
            const payload = await response.json();
            this.captureToken(response, payload);
            this.statusTarget.textContent = response.ok
                ? `Checkout prepared: ${payload.handoffReference || payload.handoffId}`
                : (payload.message || 'Checkout is not ready.');
        } finally {
            this.checkoutTarget.disabled = false;
        }
    }

    async remove(event) {
        const response = await this.request(`/api/cart/item/${event.currentTarget.dataset.itemId}`, { method: 'DELETE' });
        if (response.ok) this.render(await response.json());
    }

    request(url, options) {
        return fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                ...(options.headers || {}),
                ...(this.cartToken ? { 'X-Cart-Token': this.cartToken } : {}),
            },
        });
    }

    captureToken(response, payload) {
        const token = response.headers.get('X-Cart-Token') || payload.cartToken || '';
        if (token) {
            this.cartToken = token;
            window.localStorage.setItem('sr.cart.token', token);
        }
    }

    render(cart) {
        this.statusTarget.textContent = `Status: ${cart.status}; ${cart.itemCount} item(s)`;
        this.emptyTarget.hidden = cart.items.length !== 0;
        this.itemsTarget.replaceChildren(...cart.items.map((item) => this.itemNode(item, cart.currencyCode)));
        this.totalTarget.textContent = this.money(cart.totalMinor, cart.currencyCode);
        this.checkoutTarget.disabled = cart.items.length === 0;
    }

    itemNode(item, currencyCode) {
        const li = document.createElement('li');
        li.append(document.createTextNode(`${item.title} — ${item.quantity} × ${this.money(item.unitPriceMinor, currencyCode)} `));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = 'Remove';
        remove.dataset.itemId = item.id;
        remove.dataset.action = 'cart#remove';
        li.append(remove);
        return li;
    }

    money(amountMinor, currencyCode) {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency: currencyCode }).format(amountMinor / 100);
    }
}
