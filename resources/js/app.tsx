import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { installImageUrlNormalizer } from './lib/imageUrl';

installImageUrlNormalizer();

createInertiaApp({
    title: (title) => (title ? `${title} · IGI Canada` : 'IGI Canada'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        return pages[`./pages/${name}.tsx`]() as never;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#d80621',
    },
});
