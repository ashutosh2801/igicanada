import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function WishlistButton({ variantId, channel }: { variantId: number; channel: 'retail' | 'wholesale' }) {
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const [open, setOpen] = useState(false);

    function submit() {
        router.post('/wishlist-requests', { variant_id: variantId, sales_channel: channel, name, email }, {
            preserveScroll: true,
            onSuccess: () => { setSent(true); setOpen(false); },
        });
    }

    if (sent) return <span className="text-sm font-bold text-emerald-700">We’ll notify you when available</span>;
    if (!open) return <button type="button" onClick={() => setOpen(true)} className="rounded-full border border-stone-300 px-4 py-2 text-xs font-bold text-stone-700 transition hover:border-red-600 hover:text-red-600">Notify Me</button>;

    return <span className="flex flex-wrap items-center gap-2">
        <input type="text" value={name} onChange={event => setName(event.target.value)} placeholder="Your name" aria-label="Your name" className="w-36 rounded-full border border-stone-300 px-3 py-2 text-xs outline-none focus:border-red-600" />
        <input type="email" value={email} onChange={event => setEmail(event.target.value)} placeholder="Email address" aria-label="Email for availability notification" className="w-44 rounded-full border border-stone-300 px-3 py-2 text-xs outline-none focus:border-red-600" />
        <button type="button" disabled={!name.trim() || !email.trim()} onClick={submit} className="rounded-full bg-red-600 px-4 py-2 text-xs font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-300">Notify Me</button>
        <button type="button" onClick={() => setOpen(false)} className="text-xs font-bold text-stone-500">Cancel</button>
    </span>;
}
