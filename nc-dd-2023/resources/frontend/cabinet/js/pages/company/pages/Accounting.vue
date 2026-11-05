<template>
    <div class="title-wrap">
        <AppButtonBack />

        <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

        <div v-if="!isAccountingShow" class="btn-group ml-auto">
            <AccountingControls :companyUuid="companyUuid" />
        </div>
    </div>

    <Accounting
        :companyUuid="companyUuid"
        :addedAccountingUuid="addedAccountingUuid"
        @editItem="editItem"
    />
</template>

<script>
import { mapGetters } from 'vuex';
import Accounting from '@/components/company/Accounting';
import AccountingControls from '@/components/company/AccountingControls';
import screenMixin from '@/mixins/screenMixin';
import AppButtonBack from '@/components/base/AppButtonBack';


export default {
    name: 'CompanyPageAccounting',
    mixins: [screenMixin],
    components: {
        AppButtonBack,
        Accounting,
        AccountingControls
    },
    computed: {
        /**
         * Возвращает uuid компании
         *
         * @return {string}
         */
        companyUuid() {
            return String(this.$route.params.uuid);
        },

        ...mapGetters({
            addedAccountingUuid: 'accounting/addedAccountingUuid',
            editAccounting: 'accounting/editAccounting'
        }),

        /**
         * Показывает кнопки Ежедневного учета
         *
         * @returns {boolean}
         */
        isAccountingShow() {
            return this.windowWidth > 1340
        }
    },
    methods: {
        /**
         * Установка параметров редактирования записи
         *
         * @param  {string} operation
         * @param  {string} id
         * @return {void}
         */
        editItem({ operation, id }) {
            this.$store.dispatch('accounting/editItem', { operation, id });
        },
    },
    created() {
        this.$store.dispatch('accounting/clear');
    }
}
</script>
