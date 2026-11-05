<template>
    <div class="title-wrap title-wrap--compact">
        <AppTitle :title="$route.meta.title" />

        <div v-if="Object.keys(data).length" class="btn-group ml-auto">
            <AccountingControls v-if="!isAccountingShow" :companyUuid="uuid" />
            <CompanyImportFile :companyUuid="uuid" />
            <CompanyIndicators :companyUuid="uuid" />
        </div>
    </div>

    <AppLoader v-if="isLoading" />

    <AppAlert v-else-if="isError" type="danger" :message="message" />

    <template v-else>
        <CompanyNav />
        <CompanyDashboard />
    </template>
</template>

<script>
import API from '@/api/company';
import CompanyIndicators from '@/components/company/CompanyIndicators';
import CompanyImportFile from '@/components/modal/CompanyImportFile';
import CompanyNav from '@/components/company/CompanyNav';
import CompanyDashboard from '@/components/company/CompanyDashboard';
import AccountingControls from '@/components/company/AccountingControls';
import screenMixin from '@/mixins/screenMixin';

export default {
    name: 'CompanyShow',
    mixins: [screenMixin],
    components: {
        AccountingControls,
        CompanyImportFile,
        CompanyIndicators,
        CompanyNav,
        CompanyDashboard,
    },
    data() {
        return {
            data:      {},
            isLoading: false,
            isError:   false,
            message:   '',
        }
    },
    computed: {
        /**
         * Возвращает uuid компании
         *
         * @return {string}
         */
        uuid() {
            return this.$route.params.uuid;
        },

        /**
         * Показывает кнопки Ежедневного учета
         *
         * @returns {boolean}
         */
        isAccountingShow() {
            return this.windowWidth > 1340;
        }
    },
    methods: {
        /**
         * Инициализация компании
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isLoading = true;
            this.isError   = false;
            this.message   = '';

            try {
                const { name, description } = await API.getOne({ uuid: this.uuid });

                this.data = { name, description };
            } catch (error) {
                const { data } = error.response;

                this.isError = true;
                this.message = data.message;

                throw error;
            } finally {
                this.isLoading = false;
            }
        },
    },
    created() {
        this.initialize();
    }
}
</script>
