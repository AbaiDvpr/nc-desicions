import { createStore } from 'vuex';
import lang from '@/store/modules/lang';
import profile from '@/store/modules/profile';
import dashboard from '@/store/modules/dashboard';
import loader from '@/store/modules/loader';
import modal from '@/store/modules/modal';
import sidePopup from '@/store/modules/sidePopup';
import sidebar from '@/store/modules/sidebar';
import companyIndicators from '@/store/modules/companyIndicators';
import notification from '@/store/modules/notification';
import accounting from '@/store/modules/accounting';
import progressBar from '@/store/modules/progressBar';

const store = createStore({
    state: {},
    getters: {},
    actions: {},
    mutations: {},
    modules: {
        lang,
        profile,
        dashboard,
        loader,
        modal,
        sidePopup,
        sidebar,
        companyIndicators,
        notification,
        accounting,
        progressBar
    },
});

export default store;
