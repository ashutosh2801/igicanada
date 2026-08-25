import AuthShell from '@/components/AuthShell';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { getAllCitiesOfCountry, getCitiesOfState, getCountries, getStatesOfCountry } from '@countrystatecity/countries-browser';
import type { ICity, ICountry, IState } from '@countrystatecity/countries-browser';
import { FormEvent, useEffect, useState } from 'react';

type Props = {
    signedInAccount: {
        name: string;
        accountType: string;
        destinationLabel: string;
        destinationUrl: string;
    } | null;
};

export default function Apply({ signedInAccount }: Props) {
    const [countryCode, setCountryCode] = useState('CA');
    const [stateCode, setStateCode] = useState('');
    const [countries, setCountries] = useState<ICountry[]>([]);
    const [states, setStates] = useState<IState[]>([]);
    const [cities, setCities] = useState<ICity[]>([]);
    const [statesLoading, setStatesLoading] = useState(true);
    const [citiesLoading, setCitiesLoading] = useState(false);
    const [locationError, setLocationError] = useState(false);
    const form = useForm({
        name: '', email: '', password: '', password_confirmation: '',
        company: '', phone: '', business_number: '', tax_number: '',
        address: '', city: '', province: '', country: 'Canada', postal_code: '',
    });
    useEffect(() => {
        let active = true;

        getCountries()
            .then(items => active && setCountries(items.sort((a, b) => a.name.localeCompare(b.name))))
            .catch(() => active && setLocationError(true));

        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        let active = true;
        setStatesLoading(true);
        setCities([]);

        getStatesOfCountry(countryCode)
            .then(async items => {
                if (! active) return;
                const nextStates = items.sort((a, b) => a.name.localeCompare(b.name));
                setStates(nextStates);
                setStatesLoading(false);

                if (nextStates.length === 0) {
                    form.setData('province', 'Not applicable');
                    setCitiesLoading(true);
                    const countryCities = await getAllCitiesOfCountry(countryCode);
                    if (active) setCities(countryCities.sort((a, b) => a.name.localeCompare(b.name)));
                    if (active) setCitiesLoading(false);
                }
            })
            .catch(() => {
                if (active) setLocationError(true);
                if (active) setStatesLoading(false);
            });

        return () => {
            active = false;
        };
    }, [countryCode]);

    useEffect(() => {
        if (! stateCode) return;

        let active = true;
        setCitiesLoading(true);
        getCitiesOfState(countryCode, stateCode)
            .then(items => active && setCities(items.sort((a, b) => a.name.localeCompare(b.name))))
            .catch(() => active && setLocationError(true))
            .finally(() => active && setCitiesLoading(false));

        return () => {
            active = false;
        };
    }, [countryCode, stateCode]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/wholesale/apply', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    const field = (name: keyof typeof form.data, label: string, type = 'text', autoComplete?: string) => (
        <label className={name === 'address' ? 'block md:col-span-2' : 'block'}>
            <span className="text-sm font-semibold">{label}</span>
            <input
                type={type}
                autoComplete={autoComplete}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 outline-none focus:border-amber-800"
            />
            {form.errors[name] && <span className="mt-1 block text-sm text-red-700">{form.errors[name]}</span>}
        </label>
    );

    function selectCountry(nextCountryCode: string) {
        const country = countries.find(item => item.iso2 === nextCountryCode);

        setCountryCode(nextCountryCode);
        setStateCode('');
        form.setData(data => ({
            ...data,
            country: country?.name || '',
            province: '',
            city: '',
        }));
    }

    function selectState(nextStateCode: string) {
        const state = states.find(item => item.iso2 === nextStateCode);

        setStateCode(nextStateCode);
        form.setData(data => ({ ...data, province: state?.name || '', city: '' }));
    }

    if (signedInAccount) {
        return (
            <>
                <Head title="Apply for a wholesale account" />
                <AuthShell title="Apply for a wholesale account" intro="This application creates a new wholesale login for your business." image="/register-left.jpg">
                    <div className="rounded-2xl border-2 border-red-600 bg-red-50 p-6">
                        <p className="text-sm font-black tracking-wider text-red-700 uppercase">Already signed in</p>
                        <p className="mt-3 leading-7 text-stone-700">
                            You are signed in as <strong>{signedInAccount.name}</strong> ({signedInAccount.accountType}). Sign out first if you want to submit a separate wholesale account application.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <a href={signedInAccount.destinationUrl} className="rounded-xl bg-black px-5 py-3 text-sm font-bold text-white hover:bg-red-600">{signedInAccount.destinationLabel}</a>
                            <button type="button" onClick={() => router.post('/logout')} className="rounded-xl border-2 border-black px-5 py-3 text-sm font-bold hover:bg-black hover:text-white">Sign out to apply</button>
                        </div>
                    </div>
                </AuthShell>
            </>
        );
    }

    return (
        <>
            <Head title="Apply for a wholesale account" />
            <AuthShell title="Apply for a wholesale account" intro="Tell us about your business. IGI Canada will review the application before protected pricing is enabled." image="/register-left.jpg">
                <form onSubmit={submit} className="grid gap-5 md:grid-cols-2">
                    {field('name', 'Contact name', 'text', 'name')}
                    {field('email', 'Business email', 'email', 'email')}
                    {field('company', 'Company name', 'text', 'organization')}
                    {field('phone', 'Phone', 'tel', 'tel')}
                    {field('business_number', 'Business number')}
                    {field('tax_number', 'Tax / HST number')}
                    {field('address', 'Street address', 'text', 'street-address')}
                    <label className="block">
                        <span className="text-sm font-semibold">Country</span>
                        <select required value={countryCode} onChange={event => selectCountry(event.target.value)} autoComplete="country" className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 outline-none focus:border-red-600">
                            {countries.length === 0 && <option value="CA">Loading countries…</option>}
                            {countries.map(country => <option key={country.iso2} value={country.iso2}>{country.name}</option>)}
                        </select>
                        {form.errors.country && <span className="mt-1 block text-sm text-red-700">{form.errors.country}</span>}
                    </label>
                    <label className="block">
                        <span className="text-sm font-semibold">Province / state</span>
                        <select required={states.length > 0} disabled={statesLoading || states.length === 0} value={stateCode} onChange={event => selectState(event.target.value)} autoComplete="address-level1" className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 outline-none focus:border-red-600 disabled:bg-black/5">
                            <option value="">{statesLoading ? 'Loading provinces / states…' : states.length === 0 ? 'Not applicable' : 'Select province / state'}</option>
                            {states.map(state => <option key={state.iso2} value={state.iso2}>{state.name}</option>)}
                        </select>
                        {form.errors.province && <span className="mt-1 block text-sm text-red-700">{form.errors.province}</span>}
                    </label>
                    <label className="block">
                        <span className="text-sm font-semibold">City</span>
                        <select required value={form.data.city} disabled={statesLoading || citiesLoading || (states.length > 0 && !stateCode)} onChange={event => form.setData('city', event.target.value)} autoComplete="address-level2" className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 outline-none focus:border-red-600 disabled:bg-black/5">
                            <option value="">{states.length > 0 && !stateCode ? 'Select province / state first' : citiesLoading ? 'Loading cities…' : 'Select city'}</option>
                            {cities.map(city => <option key={city.id} value={city.name}>{city.name}</option>)}
                        </select>
                        {form.errors.city && <span className="mt-1 block text-sm text-red-700">{form.errors.city}</span>}
                    </label>
                    {locationError && <p className="text-sm font-semibold text-red-700 md:col-span-2">Location list could not be loaded. Please check your connection and reload the page.</p>}
                    {field('postal_code', 'Postal / ZIP code', 'text', 'postal-code')}
                    {field('password', 'Password', 'password', 'new-password')}
                    {field('password_confirmation', 'Confirm password', 'password', 'new-password')}
                    <button disabled={form.processing} className="rounded-xl bg-stone-950 px-5 py-3.5 font-bold text-white disabled:opacity-50 md:col-span-2">
                        {form.processing ? 'Submitting…' : 'Submit application'}
                    </button>
                </form>
                <p className="mt-6 text-sm text-stone-600">Already registered? <Link href="/login" className="font-semibold text-amber-800 hover:underline">Sign in</Link></p>
            </AuthShell>
        </>
    );
}
