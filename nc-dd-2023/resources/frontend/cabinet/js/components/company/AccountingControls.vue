<template>
    <CompanyIncomePopup
        :companyUuid="companyUuid"
        :accounting="editAccounting"
        @update="updateList"
        @close="closePopup"
    />

    <CompanyOutcomePopup
        :companyUuid="companyUuid"
        :accounting="editAccounting"
        @update="updateList"
        @close="closePopup"
    />

    <CompanyActivePopup
        :companyUuid="companyUuid"
        :accounting="editAccounting"
        @update="updateList"
        @close="closePopup"
    />

    <!-- <CompanyTransfer :uuid="uuid" /> -->
</template>

<script>
import { mapGetters } from 'vuex';
import CompanyOutcomePopup from '@/components/popup/CompanyOutcome';
import CompanyIncomePopup from '@/components/popup/CompanyIncome';
import CompanyActivePopup from '@/components/popup/CompanyActive';

export default {
    name: 'AccountingControls',
    components: {
        CompanyOutcomePopup,
        CompanyIncomePopup,
        CompanyActivePopup,
    },
    props: {
        companyUuid: {
            type: String,
            required: true
        }
    },
    computed: {
        ...mapGetters({
            addedAccountingUuid: 'accounting/addedAccountingUuid',
            editAccounting: 'accounting/editAccounting'
        })
    },
    methods: {
        /**
         * Обновляем список после добавления новых записей
         *
         * @param  {string} uuid
         * @return {void}
         */
        updateList(uuid) {
            this.$store.dispatch('accounting/updateList', uuid);
        },

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

        /**
         * Очистка параметров редактируемой записи
         *
         * @return {void}
         */
        closePopup() {
            this.$store.dispatch('accounting/clearEditItem');
        }
    }
}
</script>
