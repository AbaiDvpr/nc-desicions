<template>
    <header class="header">
        <router-link class="header__logo" :to="{ name: 'home' }">
            <img
                src="/static/images/cabinet/logo-min.svg"
                alt="Логотип"
                width="31"
                height="25"
            >
        </router-link>

        <span class="header__title">
            <AppIcon name="home" />
            Кабинет пользователя
        </span>

       <div v-if="(isSectionCompany && uuid) && isAccountingShow" class="btn-group">
           <AccountingControls :companyUuid="uuid" />
       </div>

        <div class="header__interact">
            <LangDropdown />
            <NotifierDropdown />
            <UserDropdown />
            <AppNavBurger />
        </div>
    </header>
</template>

<script>
import LangDropdown from '@/components/LangDropdown';
import NotifierDropdown from '@/components/NotifierDropdown';
import UserDropdown from '@/components/UserDropdown';
import AppNavBurger from '@/components/base/AppNavBurger';
import AccountingControls from '@/components/company/AccountingControls';
import { mapGetters } from 'vuex';
import screenMixin from '@/mixins/screenMixin';

export default {
    name: 'AppHeader',
    mixins: [screenMixin],
    components: {
        AccountingControls,
        AppNavBurger,
        LangDropdown,
        NotifierDropdown,
        UserDropdown
    },
    computed: {
        /**
         * Возвращает uuid
         *
         * @return {string}
         */
        uuid() {
            return this.$route.params.uuid;
        },

        ...mapGetters({
            addedAccountingUuid: 'accounting/addedAccountingUuid',
            editAccounting:      'accounting/editAccounting'
        }),

        /**
         * Показывает кнопки Ежедневного учета
         *
         * @returns {boolean}
         */
        isAccountingShow() {
            return this.windowWidth > 1340
        },

        /**
         * Раздел компания
         *
         * @returns {boolean}
         */
        isSectionCompany() {
            const name = this.$route.name ? this.$route.name.split('.') : [];

            return name[0] === 'companies';
        }
    }
}
</script>
