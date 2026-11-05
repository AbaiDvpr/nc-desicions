import { createApp } from 'vue';
import * as Sentry from "@sentry/vue";
import { BrowserTracing } from "@sentry/tracing";
import { vMaska } from 'maska';
import router from '@/router/router';
import store from '@/store/store';
import App from '@/App.vue';
import i18n from '@/plugins/i18n';
import VueNumberFormat from '@/plugins/numberFormat';
import { IS_PRODUCTION, SENTRY_DSN } from '@/config/app';

import AppMainLoader from '@/components/base/AppMainLoader';
import AppButton from '@/components/base/AppButton';
import AppTitle from '@/components/base/AppTitle';
import AppLoader from '@/components/base/AppLoader';
import AppAlert from '@/components/base/AppAlert';
import AppButtonLoader from '@/components/base/AppButtonLoader';
import AppModal from '@/components/base/AppModal';
import AppTabs from '@/components/base/AppTabs';
import AppTab from '@/components/base/AppTab';
import AppIcon from '@/components/base/AppIcon';

import loaderMixin from '@/mixins/loaderMixin';
import modalMixin from '@/mixins/modalMixin';
import popupMixin from '@/mixins/sidePopupMixin';
import numberMixin from '@/mixins/numberMixin';

const app = createApp(App);

if (IS_PRODUCTION) {
    Sentry.init({
        app,
        dsn: SENTRY_DSN,
        integrations: [
            new BrowserTracing({
                routingInstrumentation: Sentry.vueRouterInstrumentation(router),
                // tracePropagationTargets: ['localhost', 'my-site-url.com', /^\//],
            }),
        ],
        // Set tracesSampleRate to 1.0 to capture 100%
        // of transactions for performance monitoring.
        // We recommend adjusting this value in production
        tracesSampleRate: 1.0,
    });
}

app.use(i18n);
app.use(router);
app.use(store);
app.use(VueNumberFormat);

app.component('AppMainLoader', AppMainLoader);
app.component('AppButton', AppButton);
app.component('AppButtonLoader', AppButtonLoader);
app.component('AppTitle', AppTitle);
app.component('AppLoader', AppLoader);
app.component('AppAlert', AppAlert);
app.component('AppModal', AppModal);
app.component('AppTabs', AppTabs);
app.component('AppTab', AppTab);
app.component('AppIcon', AppIcon);

app.mixin(loaderMixin);
app.mixin(numberMixin);
app.mixin(modalMixin);
app.mixin(popupMixin);

app.directive('maska', vMaska);

app.mount('#app');
