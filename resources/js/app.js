import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const safeRoute = (...args) => {
    if (typeof window !== 'undefined' && typeof window.route === 'function') {
        return window.route(...args);
    }
    return '#';
};

createInertiaApp({
    title: (title) => title ? `${title} - Beyan Madrasa` : 'Beyan Madrasa Management',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        const activeRoute = (typeof window !== 'undefined' && typeof window.route === 'function')
            ? window.route
            : safeRoute;

        vueApp.config.globalProperties.route = activeRoute;
        vueApp.provide('route', activeRoute);

        vueApp
            .use(plugin)
            .mixin({ methods: { route: activeRoute } })
            .mount(el);
    },
});
