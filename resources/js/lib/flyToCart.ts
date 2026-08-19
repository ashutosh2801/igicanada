export function flyToCart(sourceEl: HTMLElement | null): void {
    if (!sourceEl) return;
    const cart = document.querySelector<HTMLElement>('[data-cart-target]');
    if (!cart) return;

    const sourceRect = sourceEl.getBoundingClientRect();
    const cartRect = cart.getBoundingClientRect();

    const flyer = document.createElement('img');
    const img = sourceEl.querySelector('img') || (sourceEl instanceof HTMLImageElement ? sourceEl : null);
    flyer.src = img?.src ?? '';
    flyer.style.cssText = `
        position: fixed;
        left: ${sourceRect.left}px;
        top: ${sourceRect.top}px;
        width: ${sourceRect.width}px;
        height: ${sourceRect.height}px;
        object-fit: contain;
        z-index: 9999;
        pointer-events: none;
        border-radius: 16px;
        transition: transform 0.7s cubic-bezier(0.5, -0.3, 0.7, 1), opacity 0.7s ease-in;
    `;
    document.body.appendChild(flyer);

    const dx = cartRect.left + cartRect.width / 2 - sourceRect.left - sourceRect.width / 2;
    const dy = cartRect.top + cartRect.height / 2 - sourceRect.top - sourceRect.height / 2;
    const scale = 0.15;

    requestAnimationFrame(() => {
        flyer.style.transform = `translate(${dx}px, ${dy}px) scale(${scale})`;
        flyer.style.opacity = '0.1';
    });

    setTimeout(() => {
        flyer.remove();
        cart.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.25)' }, { transform: 'scale(1)' }],
            { duration: 300, easing: 'ease-out' },
        );
    }, 700);
}
