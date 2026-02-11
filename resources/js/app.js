import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// [CHANGE 1] Set default name to Ergovision AI
const appName = import.meta.env.VITE_APP_NAME || 'Ergovision AI';

createInertiaApp({
    // [CHANGE 2] Smart Title Logic
    // If a page has a title -> "Dashboard - Ergovision AI"
    // If no title -> "Ergovision AI"
    title: (title) => title ? `${title} - ${appName}` : appName,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        // [OPTIONAL] Changed progress bar color to your Indigo brand color
        color: '#6366f1',
    },
});