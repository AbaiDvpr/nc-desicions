<template>
    <div class="title-wrap">
        <AppButtonBack />

        <h1 class="title-primary">Баланс</h1>
    </div>

    <AppLoader v-if="isMainLoading" />

    <AppAlert
        v-else-if="isError"
        type="danger"
        :message="$t('whoops')"
    />

    <template v-else>
        <div class="tabs-controls panel">
            <div class="tabs-filter">
                <DatePicker
                    class="form-group form-group--lg mb-0"
                    v-model:value="filter.period"
                    format="MMM YYYY"
                    value-type="YYYY-MM-DD"
                    type="month"
                    :range="true"
                    input-class="input"
                    separator=" - "
                    placeholder="Выберите период"
                ></DatePicker>
            </div>

            <div class="tabs-toggle">
                <button
                    :class="['tabs-toggle__btn', { 'is-active': isViewChart }]"
                    @click="toggleView()"
                >
                    <AppIcon name="chart" />
                </button>

                <button
                    :class="['tabs-toggle__btn', { 'is-active': !isViewChart }]"
                    @click="toggleView()"
                >
                    <AppIcon name="grid" />
                </button>
            </div>
        </div>

        <div class="tabs">
            <ul class="tabs-nav">
                <li
                    :class="['tabs-nav__item', { 'is-active': currentTabId === 0 }]"
                    @click="changeTab(0)"
                >Фактические показатели</li>

                <li
                    v-for="({ id }, index) in indicators"
                    :class="['tabs-nav__item', { 'is-active': currentTabId === id }]"
                    @click="changeTab(id)"
                >Плановые показатели №{{ index + 1 }}</li>
            </ul>

            <div class="tabs-content">
                <AppLoader v-if="isLoading" />

                <template v-else>
                    <FactPlanChart v-if="isViewChart" :series="series" />

                    <FactPlanTable v-else :data="tableData" title="Баланс" />
                </template>
            </div>
        </div>
    </template>
</template>

<script>
import { defineAsyncComponent } from 'vue';
import { mapGetters } from 'vuex';
import API from '@/api/company';
import notyf from '@/plugins/notyf';
import DatePicker from '@/plugins/datePicker';
import AppLoader from '@/components/base/AppLoader';
import FactPlanTable from '@/components/company/FactPlanTable';
import companyFactPlanMixin from '@/mixins/companyFactPlanMixin';
import AppButtonBack from '@/components/base/AppButtonBack';

/**
 * Раздел компании
 *
 * @type {string}
 */
const COMPANY_SECTION = 'balance';

export default {
    name: 'CompanyPageBalance',
    mixins: [companyFactPlanMixin],
    components: {
        AppButtonBack,
        DatePicker,
        FactPlanTable,
        FactPlanChart: defineAsyncComponent({
            loader: () => import(/* webpackChunkName: "FactPlanChart" */ '@/components/company/FactPlanChart.vue'),
            loadingComponent: AppLoader
        })
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

        /**
         * Возвращает раздел компании
         *
         * @return {string}
         */
        companySection() {
            return COMPANY_SECTION;
        },

        ...mapGetters({
            indicators: 'companyIndicators/indicators',
        })
    },
    methods: {
        /**
         * Инициализация фактических данных
         *
         * @return {Promise<void>}
         */
        async initData() {
            this.isMainLoading = true;
            this.isError       = false;

            try {
                const fact = await API.getBalanceReport({
                    companyUuid: this.companyUuid,
                    period: this.filter.period
                });

                this.fact = {...fact};
                this.updateChart();
                this.updateTable({...this.fact});
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Возвращает фактические данные
         *
         * @return {Promise<void>}
         */
        async getFact() {
            this.isLoading = true;

            try {
                const fact = await API.getBalanceReport({
                    companyUuid: this.companyUuid,
                    period: this.filter.period
                });

                this.fact = {...fact};
                this.updateChart();
                this.updateTable({...fact});

                notyf.success(this.$i18n.t('dataLoaded'));
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Возвращает данные факта и плана
         *
         * @param  {number} indicatorId
         * @return {Promise<void>}
         */
        async getFactPlan(indicatorId) {
            this.isLoading = true;

            const params = {
                companyUuid: this.companyUuid,
                period: this.filter.period,
                indicatorId
            };

            try {
                const fact = await API.getBalanceReport({...params});
                const plan = await API.getBalanceReportPlan({...params});

                if (Object.keys(plan.data).length) {
                    this.fact = {...fact};
                    this.plan = {...plan};
                    this.updateChart(true);
                    this.updateTable({...plan});
                } else {
                    this.resetData();
                }

                notyf.success(this.$i18n.t('dataLoaded'));
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isLoading = false;
            }
        },
    },
    created() {
        if (!this.indicators.length) {
            this.$store.dispatch('companyIndicators/getList', { companyUuid: this.companyUuid });
        }

        this.initData();
    }
}
</script>
