import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function WishlistButton({ variantId, channel }: { variantId: number; channel: 'retail' | 'wholesale' }) {
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const [open, setOpen] = useState(false);

    function submit() {
        router.post('/wishlist-requests', { variant_id: variantId, sales_channel: channel, email: email || null }, {
            preserveScroll: true,
            onSuccess: () => { setSent(true); setOpen(false); },
        });
    }

    if (sent) return <span className="text-sm font-bold text-emerald-700">Added to wishlist</span>;
    if (!open) return <button type="button" onClick={() => setOpen(true)} className="rounded-full border border-stone-300 px-4 py-2 text-xs font-bold text-stone-700 transition hover:border-red-600 hover:text-red-600">Add to wishlist</button>;

    return <span className="flex flex-wrap items-center gap-2">
        <input type="email" value={email} onChange={event => setEmail(event.target.value)} placeholder="Email (optional)" aria-label="Email for wishlist notification" className="w-44 rounded-full border border-stone-300 px-3 py-2 text-xs outline-none focus:border-red-600" />
        <button type="button" onClick={submit} className="rounded-full bg-red-600 px-4 py-2 text-xs font-bold text-white">Save wishlist</button>
        <button type="button" onClick={() => setOpen(false)} className="text-xs font-bold text-stone-500">Cancel</button>
    </span>;
}
