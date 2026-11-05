<template>
    <div class="panel overflow-hidden">
        <VueApexCharts
            height="450"
            type="line"
            :series="series"
            :options="generalOptions"
            @mounted="onMounted"
            @updated="hidePlansLegend"
            @legendClick="onLegendClick"
        />
    </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import { getFormatDate } from '@/plugins/dayjs';
import { nFormatter } from '@/helpers/helpers';

/**
 * @type {Object}
 */
const DEFAULT_OPTIONS = {
    chart: {
        toolbar: {
            tools: {
                download: true,
                selection: false,
                zoom: false,
                zoomin: false,
                zoomout: false,
                pan: false,
                reset: false
            },
        },
        fontFamily: 'Arial, sans-serif',
        foreColor: '#000',
        zoom: {
            enabled: false
        }
    },
    yaxis: {
        labels: {
            style: {
                fontSize: '14px',
            },
            formatter: nFormatter,
        },
    },
    xaxis: {
        categories: [],
        labels: {
            rotate: -45,
            style: {
                fontSize: '14px',
            },
            formatter: value => value ? getFormatDate(value, 'MM.YYYY') : ''
        },
    },
    dataLabels: {
        enabled: false
    },
    markers: {
        size: 4,
        hover: {
            sizeOffset: 2
        }
    },
    tooltip: {
        shared: false,
        y: {
            formatter: value => {
                if (value) {
                    return Number(value).toLocaleString(['ru']);
                }
            }
        }
    },
    legend: {
        fontSize: '14px',
        itemMargin: {
            horizontal: 15,
            vertical: 5
        },
        offsetY: 10,
        markers: {
            width: 28,
            height: 14,
            radius: 0,
            offsetX: -5
        },
        onItemHover: {
            highlightDataSeries: false
        }
    },
    noData: {
        text: '',
        align: 'center',
        verticalAlign: 'middle',
        offsetX: 0,
        offsetY: 0,
        style: {
            color: '#ccc',
            fontSize: '24px',
        }
    }
};

export default {
    name: 'FactPlanChart',
    components: { VueApexCharts },
    props: {
        series: {
            type: Array,
            required: true
        },
        options: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    data() {
        return {
            hasPlan: false,
        }
    },
    computed: {
        /**
         * Общие опции
         *
         * @return {Object}
         */
        generalOptions() {
            const fill = {
                opacity: this.series.map(({ type }) => type === 'area' ? 0.25 : 0.75),
                type: 'solid'
            };
            const stroke = {
                show: true,
                width: 2,
                dashArray: this.series.map(({ ncIsPlan }) => ncIsPlan ? 6 : 0),
            };
            const colors = this.series.map(({ ncColor }) => ncColor);

            const options = {
                ...DEFAULT_OPTIONS,
                fill,
                colors,
                stroke,
                ...this.options,
            }

            options.noData.text = this.$i18n.t('noDataForPeriod');

            return options;
        }
    },
    methods: {
        /**
         * Скрывает плановые легенды из списка легенд
         *
         * @param  chartContext
         * @param  config
         * @param  globals
         * @return {void}
         */
        hidePlansLegend(chartContext, { globals }) {
            const indexArray = this.series
                .map(({ ncIsPlan }, key) => ncIsPlan ? String(key) : null)
                .filter(item => item);

            indexArray.map(index => {
                if (globals.dom?.elLegendWrap.children.length) {
                    globals.dom.elLegendWrap.children[index].style.display = 'none';
                }
            });
        },

        /**
         * Скрывает все series кроме первого
         *
         * @param  chartContext
         * @return {void}
         */
        hideAllSeriesWithoutFirst(chartContext) {
            this.series.map(({ name }, index) => {
                if (index > 1) chartContext.hideSeries(name);
            });
        },

        /**
         * Обработчик mounted
         *
         * @param  chartContext
         * @param  config
         * @return {void}
         */
        onMounted(chartContext, config) {
            const findIndex = this.series.findIndex(({ncIsPlan}) => ncIsPlan);
            const hasPlan   = findIndex >= 0;

            this.hidePlansLegend(chartContext, config);

            if (hasPlan) {
                this.hideAllSeriesWithoutFirst(chartContext, config);
            }
        },

        /**
         * Скрывает плановые series на графике
         *
         * @param  chartContext
         * @param  seriesIndex
         * @return {void}
         */
        onLegendClick(chartContext, seriesIndex) {
            const groupName = this.series[seriesIndex].group;

            if (!groupName) return;

            const seriesPlanIndex = this.series.findIndex(({ ncIsPlan, group }) => {
                return ncIsPlan && group === groupName;
            });
            const seriesPlanName = this.series[seriesPlanIndex]?.name;

            if (seriesPlanName) chartContext.toggleSeries(seriesPlanName);
        },
    }
}
</script>
