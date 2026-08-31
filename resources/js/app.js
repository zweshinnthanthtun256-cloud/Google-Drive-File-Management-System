import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const pages = import.meta.glob('./Pages/**/*.vue');

console.log('1. APP.JS LOADED');
console.log('2. AVAILABLE PAGES:', pages);

createInertiaApp({
    resolve: async (name) => {
        console.log('3. RESOLVE CALLED');
        console.log('4. PAGE NAME:', name);

        const path = `./Pages/${name}.vue`;

        console.log('5. PATH:', path);
        console.log('6. EXISTS:', pages[path] !== undefined);

        const page = await resolvePageComponent(path, pages);

        console.log('7. RESOLVED PAGE:', page);
        console.log('8. PAGE COMPONENT:', page?.default);

        return page;
    },

    setup({ el, App, props, plugin }) {
        console.log('9. SETUP CALLED');
        console.log('10. EL:', el);
        console.log('11. APP:', App);
        console.log('12. PROPS:', props);

        const vueApp = createApp({
            render: () => h(App, props),
        });

        console.log('13. VUE APP CREATED');

        vueApp.use(plugin);

        console.log('14. INERTIA PLUGIN INSTALLED');

        vueApp.mount(el);

        console.log('15. VUE MOUNTED');
    },
});
