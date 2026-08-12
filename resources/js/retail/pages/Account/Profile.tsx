import AccountShell from '../../components/AccountShell';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, ReactElement, cloneElement, useRef, useState } from 'react';

export default function Profile({ profile }: { profile: { name: string; email: string; gender: string | null; phone: string | null; avatar: string | null } }) {
    const form = useForm({
        name: profile.name,
        email: profile.email,
        gender: profile.gender ?? '',
        phone: profile.phone ?? '',
        avatar: null as File | null,
    });
    const [avatarPreview, setAvatarPreview] = useState<string | null>(profile.avatar);
    const fileInput = useRef<HTMLInputElement>(null);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.put('/account/profile', { forceFormData: true, preserveScroll: true, onSuccess: () => form.setData('avatar', null) });
    }

    function pickAvatar(file: File | null) {
        form.setData('avatar', file);
        setAvatarPreview(file ? URL.createObjectURL(file) : profile.avatar);
    }

    return (
        <>
            <Head title="Profile" />
            <AccountShell title="Profile">
                <form onSubmit={submit} className="grid gap-5 rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-8">
                    <div className="flex items-center gap-4">
                        <div className="grid size-16 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-[#d80621] to-black text-lg font-black text-white">
                            {avatarPreview ? <img src={avatarPreview} alt="Profile picture" className="size-full object-cover" /> : initials(profile.name)}
                        </div>
                        <div>
                            <input ref={fileInput} type="file" accept="image/*" onChange={e => pickAvatar(e.target.files?.[0] ?? null)} className="hidden" />
                            <button type="button" onClick={() => fileInput.current?.click()} className="rounded-full bg-leather-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-leather-700">Change picture</button>
                            <p className="mt-1 text-xs text-stone-500">JPG, PNG or WebP up to 2 MB.</p>
                        </div>
                    </div>

                    <Field label="Full name" error={form.errors.name}><input value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /></Field>
                    <Field label="Email" error={form.errors.email}><input type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                    <Field label="Phone" error={form.errors.phone}><input type="tel" value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} /></Field>
                    <Field label="Gender" error={form.errors.gender}>
                        <select value={form.data.gender} onChange={e => form.setData('gender', e.target.value)}>
                            <option value="">Prefer not to say</option>
                            <option>Female</option>
                            <option>Male</option>
                            <option>Non-binary</option>
                            <option>Other</option>
                        </select>
                    </Field>
                    <div className="flex flex-wrap items-center gap-4">
                        <button disabled={form.processing} className="rounded-full bg-leather-900 px-6 py-3 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Saving…' : 'Save changes'}</button>
                        {form.recentlySuccessful && <span className="text-sm font-bold text-emerald-700">Saved.</span>}
                    </div>
                </form>
            </AccountShell>
        </>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }> }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-2 block text-xs text-red-700">{error}</span>}</label>;
}

function initials(name: string) {
    return name.trim().split(/\s+/).slice(0, 2).map(part => part[0]?.toUpperCase()).join('') || 'U';
}
