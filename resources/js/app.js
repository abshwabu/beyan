import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';

const safeRoute = (...args) => {
    if (typeof window !== 'undefined' && typeof window.route === 'function') {
        return window.route(...args);
    }
    return '#';
};

// Synchronize document <html> dir and lang attributes on every page navigation
if (typeof document !== 'undefined') {
    router.on('navigate', (event) => {
        const page = event.detail.page;
        // The landing page handles its own direction when translated into Arabic.
        // All internal dashboard and management pages are authored in English and must always stay LTR.
        const direction = page.component === 'Landing' && page.props.direction ? page.props.direction : 'ltr';
        const locale = page.props.i18n?.locale || 'en';
        document.documentElement.setAttribute('dir', direction);
        document.documentElement.setAttribute('lang', locale);
    });
}

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
