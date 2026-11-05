import { createI18n } from 'vue-i18n';

const { locale, fallbackLocale } = window.data;

const i18n = createI18n({
    locale,
    fallbackLocale,
    messages: loadLocaleMessages()
});

export function setI18nLanguage(i18n, locale) {
    if (i18n.mode === 'legacy') {
        i18n.global.locale = locale;
    } else {
        i18n.global.locale.value = locale;
    }
    document.querySelector('html').setAttribute('lang', locale);
}

function loadLocaleMessages() {
    const locales = require.context('@/lang', true, /[A-Za-z0-9-_,\s]+\.json$/i);
    const messages = {};

    locales.keys().forEach(key => {
        const matched = key.match(/([A-Za-z0-9-_]+)\./i);

        if (matched && matched.length > 1) {
            const locale = matched[1];

            messages[locale] = locales(key);
        }
    })
    return messages
}

;(function () {
    setI18nLanguage(i18n, locale);
})();

export default i18n;
