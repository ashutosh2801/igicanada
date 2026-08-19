import PublicShell from '@/components/PublicShell';
import { getCountries } from '@countrystatecity/countries-browser';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

type Address = { id: number; address: string; city: string; province: string; country: string; postalCode: string; phone: string };
type ShippingRate = { code: string; name: string; price: string; transitDays: number | null; deliveryDate: string | null };
type Tax = { label: string; amount: string; rate: number };

export default function Checkout({ addresses, paypalConfigured, subtotal }: { addresses: Address[]; paypalConfigured: boolean; subtotal: string }) {
    const first = addresses[0];
    const [rates, setRates] = useState<ShippingRate[]>([]);
    const [ratesLoading, setRatesLoading] = useState(false);
    const [ratesError, setRatesError] = useState<string | null>(null);
    const [tax, setTax] = useState<Tax | null>(null);
    const form = useForm({
        address_id: first?.id || null,
        address: first?.address || '',
        city: first?.city || '',
        province: first?.province || '',
        country: first?.country || 'Canada',
        country_code: first?.country?.toLowerCase() === 'canada' || !first ? 'CA' : '',
        postal_code: first?.postalCode || '',
        phone: first?.phone || '',
        notes: '',
        shipping_service_code: '',
        payment_option: paypalConfigured ? 'paypal' : 'enquiry',
    });

    useEffect(() => {
        if (!form.data.address_id) {
            form.setData({ ...form.data, address: '', city: '', province: '', country: 'Canada', country_code: 'CA', postal_code: '', phone: '' });
            return;
        }
        const selected = addresses.find((address) => address.id === Number(form.data.address_id));
        if (selected) form.setData({
            ...form.data,
            address: selected.address || '',
            city: selected.city || '',
            province: selected.province || '',
            country: selected.country || '',
            country_code: selected.country?.toLowerCase() === 'canada' ? 'CA' : selected.country?.toLowerCase() === 'united states' ? 'US' : '',
            postal_code: selected.postalCode || '',
            phone: selected.phone || '',
            shipping_service_code: '',
        });
    }, [form.data.address_id]);

    useEffect(() => {
        if (!form.data.country || form.data.country_code) return;
        let active = true;
        getCountries().then((countries) => {
            if (!active) return;
            const match = countries.find((country) => country.name.toLowerCase() === form.data.country.trim().toLowerCase());
            if (match) form.setData('country_code', match.iso2);
        });

        return () => { active = false; };
    }, [form.data.country, form.data.country_code]);

    useEffect(() => {
        setRates([]);
        setRatesError(null);
        setTax(null);
        form.setData('shipping_service_code', '');

        if (!form.data.country_code || !form.data.postal_code.trim()) return;

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setRatesLoading(true);
            try {
                const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';
                const response = await fetch('/checkout/shipping-rates', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ country: form.data.country, country_code: form.data.country_code, postal_code: form.data.postal_code, province: form.data.province }),
                    signal: controller.signal,
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Shipping rates are unavailable.');
                setRates(payload.rates || []);
                setTax(payload.tax ?? null);
            } catch (error) {
                if (error instanceof DOMException && error.name === 'AbortError') return;
                setRatesError(error instanceof Error ? error.message : 'Shipping rates are unavailable.');
            } finally {
                if (!controller.signal.aborted) setRatesLoading(false);
            }
        }, 500);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [form.data.country, form.data.country_code, form.data.postal_code, form.data.province]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/checkout');
    }

    const cartError = (form.errors as typeof form.errors & { cart?: string }).cart;

    return (
        <PublicShell>
            <Head title="Checkout" />
                <main className="mx-auto min-h-[40rem] max-w-4xl px-6 py-14">
                    <Link href="/cart" className="mb-7 inline-block text-sm font-bold text-red-600">← Back to cart</Link>
                    <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">Checkout</p>
                    <h1 className="mt-3 text-4xl font-black">Shipping details</h1>
                    <p className="mt-3 text-stone-600">Standard Shipping is calculated from the same wholesale order-value slabs used on the previous website.</p>
                    <form onSubmit={submit} className="mt-10 grid gap-5 rounded-2xl bg-white p-7 ring-1 ring-stone-200 md:grid-cols-2">
                        {addresses.length > 0 && <label className="md:col-span-2"><span className="flex items-center justify-between"><span className="text-sm font-semibold">Saved address</span><Link href="/account/addresses" className="text-xs font-bold text-red-600">Manage addresses</Link></span><select value={form.data.address_id || ''} onChange={(event) => form.setData('address_id', event.target.value ? Number(event.target.value) : null)} className="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3"><option value="">Enter another address</option>{addresses.map((address) => <option key={address.id} value={address.id}>{address.address}, {address.city}</option>)}</select></label>}
                        <Field label="Street address" value={form.data.address} error={form.errors.address} onChange={(value) => form.setData('address', value)} wide />
                        <Field label="City" value={form.data.city} error={form.errors.city} onChange={(value) => form.setData('city', value)} />
                        <Field label="Province / state" value={form.data.province} error={form.errors.province} onChange={(value) => form.setData('province', value)} />
                        <Field label="Country" value={form.data.country} error={form.errors.country} onChange={(value) => form.setData({ ...form.data, country: value, country_code: '', shipping_service_code: '' })} />
                        <Field label="Postal / ZIP code" value={form.data.postal_code} error={form.errors.postal_code} onChange={(value) => form.setData('postal_code', value)} />
                        <Field label="Phone" value={form.data.phone} error={form.errors.phone} onChange={(value) => form.setData('phone', value)} />
                        <section className="rounded-2xl border border-stone-200 bg-stone-50 p-5 md:col-span-2">
                            <div className="flex items-center justify-between gap-4">
                                <div><p className="font-black">Standard Shipping Charges</p><p className="mt-1 text-sm text-stone-600">Product subtotal: ${subtotal} CAD. Available within Canada and the United States.</p></div>
                                <span className="rounded-full bg-amber-700 px-3 py-1 text-xs font-bold text-white">STANDARD</span>
                            </div>
                            {ratesLoading && <p className="mt-4 text-sm font-semibold text-stone-600">Calculating Standard Shipping…</p>}
                            {ratesError && <p className="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">{ratesError}</p>}
                            {!ratesLoading && !ratesError && rates.length === 0 && <p className="mt-4 text-sm text-stone-600">Enter a supported country and postal / ZIP code to calculate shipping.</p>}
                            {rates.length > 0 && <div className="mt-4 grid gap-3">{rates.map((rate) => <label key={rate.code} className={'flex cursor-pointer items-center justify-between gap-4 rounded-xl border p-4 ' + (form.data.shipping_service_code === rate.code ? 'border-red-600 bg-red-50' : 'border-stone-200 bg-white')}><span className="flex items-start gap-3"><input type="radio" name="shipping_service_code" value={rate.code} checked={form.data.shipping_service_code === rate.code} onChange={() => form.setData('shipping_service_code', rate.code)} className="mt-1 accent-red-600" /><span><strong className="block">{rate.name}</strong><span className="text-xs text-stone-500">{rate.deliveryDate ? `Expected ${rate.deliveryDate}` : rate.transitDays ? `${rate.transitDays} business days` : rate.code}</span></span></span><strong>${rate.price} CAD</strong></label>)}</div>}
                            {form.errors.shipping_service_code && <p className="mt-3 text-sm text-red-700">{form.errors.shipping_service_code}</p>}
                        </section>
                        <section className="rounded-2xl border border-stone-200 bg-stone-50 p-5 md:col-span-2">
                            <div className="flex items-center justify-between gap-4"><span className="text-sm font-semibold">Order subtotal</span><strong>${subtotal} CAD</strong></div>
                            {form.data.shipping_service_code && rates.find(r => r.code === form.data.shipping_service_code) && <div className="mt-3 flex items-center justify-between gap-4"><span className="text-sm font-semibold">{rates.find(r => r.code === form.data.shipping_service_code)?.name}</span><strong>${rates.find(r => r.code === form.data.shipping_service_code)?.price} CAD</strong></div>}
                            {tax && Number(tax.amount) > 0 && <div className="mt-3 flex items-center justify-between gap-4"><span className="text-sm font-semibold">{tax.label} ({(tax.rate * 100).toFixed(2)}%)</span><strong>${tax.amount} CAD</strong></div>}
                            {form.data.shipping_service_code && tax && <div className="mt-4 flex items-center justify-between gap-4 border-t border-stone-300 pt-3 text-base font-black"><span>Total</span><strong>${(parseFloat(subtotal) + (parseFloat(rates.find(r => r.code === form.data.shipping_service_code)?.price ?? '0')) + parseFloat(tax.amount)).toFixed(2)} CAD</strong></div>}
                        </section>
                        <label className="md:col-span-2"><span className="text-sm font-semibold">Order notes</span><textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} rows={4} className="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3" /></label>
                        <section className="grid gap-3 md:col-span-2 md:grid-cols-2">
                            <label className={'cursor-pointer rounded-2xl border p-5 ' + (form.data.payment_option === 'paypal' ? 'border-red-600 bg-red-50' : 'border-stone-200')}>
                                <span className="flex items-start gap-3"><input type="radio" name="payment_option" value="paypal" disabled={!paypalConfigured} checked={form.data.payment_option === 'paypal'} onChange={() => form.setData('payment_option', 'paypal')} className="mt-1 accent-red-600" /><span><strong className="block">Pay through PayPal</strong><span className="mt-1 block text-sm text-stone-600">Continue securely to PayPal after placing the order.</span>{!paypalConfigured && <span className="mt-2 block text-xs font-semibold text-amber-800">Temporarily unavailable</span>}</span></span>
                            </label>
                            <label className={'cursor-pointer rounded-2xl border p-5 ' + (form.data.payment_option === 'enquiry' ? 'border-red-600 bg-red-50' : 'border-stone-200')}>
                                <span className="flex items-start gap-3"><input type="radio" name="payment_option" value="enquiry" checked={form.data.payment_option === 'enquiry'} onChange={() => form.setData('payment_option', 'enquiry')} className="mt-1 accent-red-600" /><span><strong className="block">Submit order enquiry</strong><span className="mt-1 block text-sm text-stone-600">Send the order to IGI Canada without online payment.</span></span></span>
                            </label>
                            {form.errors.payment_option && <p className="text-sm text-red-700 md:col-span-2">{form.errors.payment_option}</p>}
                        </section>
                        {cartError && <p className="text-sm text-red-700 md:col-span-2">{cartError}</p>}
                        <button disabled={form.processing || !form.data.shipping_service_code} className="rounded-xl bg-stone-950 px-5 py-4 font-bold text-white disabled:cursor-not-allowed disabled:opacity-50 md:col-span-2">{form.processing ? 'Submitting…' : form.data.payment_option === 'paypal' ? 'Place order & continue to PayPal' : 'Submit order enquiry'}</button>
                    </form>
                </main>
        </PublicShell>
    );
}

function Field({ label, value, error, onChange, wide = false }: { label: string; value: string; error?: string; onChange: (value: string) => void; wide?: boolean }) {
    return <label className={wide ? 'md:col-span-2' : ''}><span className="text-sm font-semibold">{label}</span><input value={value} onChange={(event) => onChange(event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3" />{error && <span className="mt-1 block text-sm text-red-700">{error}</span>}</label>;
}
