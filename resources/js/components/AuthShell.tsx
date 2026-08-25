import PublicShell from '@/components/PublicShell';
import { ReactNode } from 'react';

export default function AuthShell({ title, intro, image, children }: { title: string; intro: string; image?: string; children: ReactNode }) {
    return (
        <PublicShell>
        <div className="grid min-h-[42rem] bg-white lg:grid-cols-[0.9fr_1.1fr]">
            <aside className="hidden bg-stone-950 p-12 text-white lg:flex lg:flex-col lg:justify-between relative overflow-hidden">
                {image && <img src={image} alt="" className="absolute inset-0 h-full w-full object-cover opacity-40" />}
                <div className="relative z-10">
                    <p className="text-sm font-black tracking-[0.2em] text-red-500 uppercase">IGI Canada wholesale</p>
                </div>
                <div className="relative z-10">
                    <p className="text-sm font-bold tracking-[0.18em] text-amber-300 uppercase">Wholesale, simplified</p>
                    <p className="mt-5 max-w-lg text-5xl leading-tight font-black tracking-tight">Your products, pricing and repeat orders in one place.</p>
                </div>
                <p className="relative z-10 text-sm text-stone-400">Existing wholesale accounts: reset your password once to securely activate the new account.</p>
            </aside>
            <main className="flex items-center justify-center px-6 py-16">
                <div className="w-full max-w-lg">
                    <h1 className="text-4xl font-black tracking-tight">{title}</h1>
                    <p className="mt-3 leading-7 text-stone-600">{intro}</p>
                    <div className="mt-9">{children}</div>
                </div>
            </main>
        </div>
        </PublicShell>
    );
}
