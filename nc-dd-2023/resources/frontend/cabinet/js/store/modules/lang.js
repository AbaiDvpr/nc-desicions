import * as types from '@/store/mutation-types';
import i18n, { setI18nLanguage } from '@/plugins/i18n';
import { setPageTitle, setPageLocaleInUrl } from '@/helpers/helpers';

const { locale, locales } = window.data;

const state = {
    locale,
    locales
};

const getters = {
    locale: state => state.locale,
    locales: state => state.locales
};

const mutations = {
    [types.SET_LOCALE](state, { locale }) {
        state.locale = locale
    }
};

const actions = {
    setLocale({ commit, state }, { locale, routeName }) {
        const oldLocale = state.locale;

        setI18nLanguage(i18n, locale);
        setPageTitle(i18n.global.t(routeName));

        commit(types.SET_LOCALE, { locale });

        setPageLocaleInUrl(oldLocale);
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
