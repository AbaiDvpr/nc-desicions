import { convertHexToRGBA } from '@/helpers/helpers';
import dayjs from 'dayjs';

/**
 * Миксин для страниц компании ОПУ, ДДС и Баланса
 */

/**
 * Дефолтные данные
 *
 * @type {{data: {}, periods: *[]}}
 */
const DEFAULT_DATA = {
    data:    {},
    periods: []
};

export default {
    data() {
        return {
            isMainLoading: false,
            isLoading:     false,
            isError:       false,
            isViewChart:   true,
            currentTabId:  0,
            fact:          {...DEFAULT_DATA},
            plan:          {...DEFAULT_DATA},
            tableData:     {...DEFAULT_DATA},
            series:        [],
            filter:        {
                period: [
                    dayjs().subtract(6, 'month').startOf('month').format('YYYY-MM-DD'),
                    dayjs().add(6, 'month').endOf('month').format('YYYY-MM-DD'),
                ],
            }
        }
    },
    watch: {
        filter: {
            handler: function () {
                if (this.currentTabId) {
                    this.getFactPlan(this.currentTabId);
                } else {
                    this.getFact();
                }
            },
            deep: true
        }
    },
    methods: {
        /**
         * Переключает вид отображения данных
         *
         * @return {void}
         */
        toggleView() {
            this.isViewChart = !this.isViewChart;
        },

        /**
         * Переключает отчеты
         *
         * @param {number} id
         * @return {void}
         */
        changeTab(id = 0) {
            if (this.isLoading) return;
            if (this.currentTabId === id) return;

            this.currentTabId = id;

            if (id) {
                this.getFactPlan(id);
            } else {
                this.getFact();
            }
        },

        /**
         * Обновляет данные в графике
         *
         * @return {void}
         */
        updateChart(isPlan = false) {
            if (!isPlan) {
                this.plan = {...DEFAULT_DATA};
            }

            let series = [
                ...this.getFactSeries(),
                ...(isPlan ? this.getPlanSeries() : [])
            ];

            // Для корректной работы графика необходимо отсортировать данные по группам,
            // Например: Себестоимость, Себестоимость (план) и т.д.
            if (isPlan) {
                const seriesByGroup = {};
                const seriesSorted = [];

                series.map(item => {
                    seriesByGroup[item.group] = seriesByGroup[item.group] ?? [];
                    seriesByGroup[item.group].push(item);
                });

                Object.keys(seriesByGroup).map(key => {
                    seriesSorted.push(...seriesByGroup[key])
                });

                series = [...seriesSorted];
            }

            this.series = [...series];
        },

        /**
         * Возвращает фактические данные для графика
         *
         * @return {Array}
         */
        getFactSeries() {
            const series = [];

            Object.values(this.fact.data).map(({ name, data, isShowChart, chartOptions }) => {
                if (!isShowChart) return;

                const dataFact = data.map(({ period, value }) => ({
                    x: period,
                    y: value,
                    fillColor: chartOptions.ncColor,
                    strokeColor: 'transparent',
                }));

                series.push({
                    name,
                    data: [...dataFact],
                    ...chartOptions
                });
            });

            return series;
        },

        /**
         * Возвращает плановые данные для графика
         *
         * @return {Array}
         */
        getPlanSeries() {
            const series = [];

            Object.values(this.plan.data).map(({ name, data, isShowChart, chartOptions }) => {
                if (!isShowChart) return;

                const dataPlan = data.map(({ period, value }) => ({
                    x:           period,
                    y:           value,
                    fillColor:   convertHexToRGBA(chartOptions.ncColor, 0.2),
                    strokeColor: chartOptions.ncColor,
                }));

                series.push({
                    name:     `${name} (план)`,
                    data:     [...dataPlan],
                    ncIsPlan: true,
                    ...chartOptions,
                });
            });

            return series;
        },

        /**
         * Обновляет данные в таблице
         *
         * @param  {Object} data
         * @return {void}
         */
        updateTable(data = { periods: [], data: {} }) {
            this.tableData = data;
        },

        /**
         * Сбрасывает данные
         *
         * @return {void}
         */
        resetData() {
            this.fact      = {...DEFAULT_DATA};
            this.plan      = {...DEFAULT_DATA};
            this.tableData = {...DEFAULT_DATA};
            this.series    = [];
        }
    }
}
