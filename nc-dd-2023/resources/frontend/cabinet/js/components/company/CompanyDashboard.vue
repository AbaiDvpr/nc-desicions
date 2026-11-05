<template>
    <div class="row">
        <div class="col-12 col-md-6">
            <AppLoader v-if="opuNetProfitDonut.isLoading" />

            <WidgetDonutTinyExt
                v-else
                :data="opuNetProfitDonut.data"
                :cost="opuNetProfitDonut.cost"
                :title="opuNetProfitDonut.title"
            />
        </div>

        <div class="col-12 col-md-6">
            <AppLoader v-if="ddsCashByEndPeriodDonut.isLoading" />

            <WidgetDonutTiny
                v-else
                :series="ddsCashByEndPeriodDonut.series"
                :options="ddsCashByEndPeriodDonut.options"
                :title="ddsCashByEndPeriodDonut.title"
                :cost="ddsCashByEndPeriodDonut.cost"
            />
        </div>

        <div class="col-12 col-xl-6">
            <AppLoader v-if="opuNetProfitArea.isLoading" />

            <WidgetArea
                v-else
                :series="opuNetProfitArea.series"
                :type="opuNetProfitArea.type"
                :title="opuNetProfitArea.title"
                :period="opuNetProfitArea.period"
            />
        </div>

        <div class="col-12 col-xl-6">
            <AppLoader v-if="ddsCashByEndPeriodArea.isLoading" />

            <WidgetArea
                v-else
                :series="ddsCashByEndPeriodArea.series"
                :type="ddsCashByEndPeriodArea.type"
                :title="ddsCashByEndPeriodArea.title"
                :period="ddsCashByEndPeriodArea.period"
            />
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import { defineAsyncComponent } from 'vue';
import API from '@/api/v1/widgets';
import dayjs from 'dayjs';
import notyf from '@/plugins/notyf';
import AppLoader from '@/components/base/AppLoader';

export default {
    name: 'CompanyDashboard',
    components: {
        WidgetArea: defineAsyncComponent({
            loader: () => import(/* webpackChunkName: "WidgetArea" */ '@/components/widget/WidgetArea.vue'),
            loadingComponent: AppLoader
        }),
        WidgetDonutTiny: defineAsyncComponent({
            loader: () => import(/* webpackChunkName: "WidgetDonutTiny" */ '@/components/widget/WidgetDonutTiny.vue'),
            loadingComponent: AppLoader
        }),
        WidgetDonutTinyExt: defineAsyncComponent({
            loader: () => import(/* webpackChunkName: "WidgetDonutTinyExt" */ '@/components/widget/WidgetDonutTinyExt.vue'),
            loadingComponent: AppLoader
        })
    },
    data() {
        return {
            filter:                  {
                period: [
                    dayjs().subtract(1, 'year').startOf('month').format('YYYY-MM-DD'),
                    dayjs().endOf('month').format('YYYY-MM-DD'),
                ]
            },
            opuNetProfitDonut:       {
                isLoading: false,
                data:      [],
                title:     'Чистая прибыль',
                cost:      '0 ₸',
                modify:    'widget-donut-tiny--xs',
            },
            opuNetProfitArea:        {
                isLoading: false,
                series:    [],
                type:      'success',
                title:     'Чистая прибыль',
                period:    '1 год',
            },
            ddsCashByEndPeriodDonut: {
                isLoading: false,
                series:    [30, 70],
                options:   {
                    colors:  ['#DF3131', '#7B809A'],
                    tooltip: { enabled: false }
                },
                title:     'Денежные средства на конец периода',
                cost:      '0 ₸',
                modify:    'widget-donut-tiny--xs',
            },
            ddsCashByEndPeriodArea:  {
                isLoading: false,
                series:    [],
                type:      'danger',
                title:     'Денежные средства на конец периода',
                period:    '1 год',
            }
        }
    },
    computed: {
        /**
         * Возвращает uuid компании
         *
         * @return {string}
         */
        companyUuid() {
            return this.$route.params.uuid;
        },

        ...mapGetters({
            addedAccountingUuid: 'accounting/addedAccountingUuid',
        })
    },
    watch: {
        'addedAccountingUuid': function() {
            this.getOpuNetProfitDonut();
            this.getOpuNetProfitArea();
            this.getDdsCashByEndPeriodDonut();
            this.getDdsCashByEndPeriodArea();
        }
    },
    methods: {
        /**
         * Возвращает чистую прибыль для графика donut
         *
         * @return {Promise<void>}
         */
        async getOpuNetProfitDonut() {
            this.opuNetProfitDonut.isLoading = true;

            try {
                const { data, amount } = await API.getOpuNetProfitDonut({
                    companyUuid: this.companyUuid,
                    period:      this.filter.period,
                });

                this.opuNetProfitDonut.data = Object.values(data);
                this.opuNetProfitDonut.cost = `${Number(amount).toLocaleString(['ru'])} ₸`;
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.opuNetProfitDonut.isLoading = false;
            }
        },

        /**
         * Возвращает чистую прибыль для графика area
         *
         * @return {Promise<void>}
         */
        async getOpuNetProfitArea() {
            this.opuNetProfitArea.isLoading = true;

            try {
                const { name, ncColor, data } = await API.getOpuNetProfitArea({
                    companyUuid: this.companyUuid,
                    period: this.filter.period
                });

                if (data) {
                    this.opuNetProfitArea.series = [{
                        name,
                        ncColor,
                        data: data.map(({ period, value }) => ({ x: period, y: value }))
                    }];
                }
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.opuNetProfitArea.isLoading = false;
            }
        },

        /**
         * Возвращает денежные средства на конец периода для графика donut
         *
         * @return {Promise<void>}
         */
        async getDdsCashByEndPeriodDonut() {
            this.ddsCashByEndPeriodDonut.isLoading = true;

            try {
                const { amount } = await API.getDdsCashByEndPeriodDonut({
                    companyUuid: this.companyUuid,
                    period: this.filter.period
                });

                if (amount) {
                    this.ddsCashByEndPeriodDonut.cost = `${Number(amount).toLocaleString(['ru'])} ₸`;
                }
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.ddsCashByEndPeriodDonut.isLoading = false;
            }
        },

        /**
         * Возвращает денежные средства на конец периода для графика area
         *
         * @return {Promise<void>}
         */
        async getDdsCashByEndPeriodArea() {
            this.ddsCashByEndPeriodArea.isLoading = true;

            try {
                const { name, ncColor, data } = await API.getDdsCashByEndPeriodArea({
                    companyUuid: this.companyUuid,
                    period: this.filter.period
                });

                if (data) {
                    this.ddsCashByEndPeriodArea.series = [{
                        name,
                        ncColor,
                        data: data.map(({ period, value }) => ({ x: period, y: value }))
                    }];
                }
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.ddsCashByEndPeriodArea.isLoading = false;
            }
        }
    },
    created() {
        this.getOpuNetProfitDonut();
        this.getOpuNetProfitArea();
        this.getDdsCashByEndPeriodDonut();
        this.getDdsCashByEndPeriodArea();
    }
}
</script>
