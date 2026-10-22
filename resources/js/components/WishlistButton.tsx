import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function WishlistButton({ variantId, channel }: { variantId: number; channel: 'retail' | 'wholesale' }) {
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    function submit() {
        router.post('/wishlist-requests', { variant_id: variantId, sales_channel: channel, name, email }, {
            preserveScroll: true,
            onStart: () => setSubmitting(true),
            onFinish: () => setSubmitting(false),
            onSuccess: () => { setSent(true); setOpen(false); },
        });
    }

    useEffect(() => {
        if (!open) return;

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') setOpen(false);
        }

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [open]);

    if (sent) return <span className="text-sm font-bold text-emerald-700">We’ll notify you when available</span>;
    if (!open) return <button type="button" onClick={() => setOpen(true)} className="rounded-full border border-stone-300 px-4 py-2 text-xs font-bold text-stone-700 transition hover:border-red-600 hover:text-red-600">Notify Me</button>;

    return <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="notify-me-title" onMouseDown={event => { if (event.target === event.currentTarget) setOpen(false); }}>
        <div className="w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-2xl">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h2 id="notify-me-title" className="text-xl font-black text-stone-900">Notify Me</h2>
                    <p className="mt-1 text-sm text-stone-600">Enter your details and we’ll contact you when this item is available.</p>
                </div>
                <button type="button" onClick={() => setOpen(false)} aria-label="Close" className="text-2xl leading-none text-stone-400 hover:text-stone-900">×</button>
            </div>
            <div className="mt-5 grid gap-3">
                <input autoFocus type="text" value={name} onChange={event => setName(event.target.value)} placeholder="Your name" aria-label="Your name" className="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none focus:border-red-600" />
                <input type="email" value={email} onChange={event => setEmail(event.target.value)} placeholder="Email address" aria-label="Email for availability notification" className="w-full rounded-xl border border-stone-300 px-4 py-3 text-sm outline-none focus:border-red-600" />
            </div>
            <div className="mt-5 flex justify-end gap-3">
                <button type="button" onClick={() => setOpen(false)} className="rounded-full px-4 py-2 text-sm font-bold text-stone-600 hover:bg-stone-100">Cancel</button>
                <button type="button" disabled={submitting || !name.trim() || !email.trim()} onClick={submit} className="rounded-full bg-red-600 px-5 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-300">{submitting ? 'Sending…' : 'Notify Me'}</button>
            </div>
        </div>
    </div>;
}
