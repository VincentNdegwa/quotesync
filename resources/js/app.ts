import { createInertiaApp } from '@inertiajs/vue3';
import { MotionPlugin } from '@vueuse/motion';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { vCan } from '@/directives/can';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import BusinessSetupLayout from '@/layouts/business-setup/Layout.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        const pinia = createPinia();

        app.use(plugin);
        app.use(MotionPlugin);
        app.use(pinia);

        app.directive('can', vCan);

        if (el) {
            app.mount(el);
        }
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('public/'):
                return null;
            case name.startsWith('portal/Auth'):
                return AuthLayout;
            case name.startsWith('portal/'):
                return PortalLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name === 'settings/WorkspaceSettings':
            case name.startsWith('settings/setup/'):
                return [AppLayout, BusinessSetupLayout];
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
