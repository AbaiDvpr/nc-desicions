<template>
    <div :class="['widget', modify]">
        <div :class="{'widget__chart widget__chart--mix' : true, [`widget__chart--${type}`]: type}">
            <VueApexCharts
                type="area"
                height="220"
                :series="series"
                :options="generalOptions"
            />
        </div>

        <h2 class="widget__title">{{ title }}</h2>

        <p v-if="subtitle" class="widget__subtitle">{{ subtitle }}</p>

        <hr class="widget__split">

        <div class="widget__period">
            {{ $t('forPeriod') }}:
            <strong>{{ period }}</strong>
        </div>
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
            show: false
        },
        fontFamily: 'Arial, sans-serif',
        foreColor: '#fff',
        background: 'transparent',
        zoom: {
            enabled: false
        }
    },
    grid: {
        show: true,
        borderColor: 'rgba(255,255,255, 0.5)',
        strokeDashArray: 10,
        position: 'back',
        xaxis: {
            lines: {
                show: true
            }
        },
        yaxis: {
            lines: {
                show: true,
            }
        },
        padding: {
            top: 0,
            right: 8,
            bottom: 0,
            left: 8
        }
    },
    xaxis: {
        labels: {
            rotate: -45,
            formatter: value => value ? getFormatDate(value, 'MM.YYYY') : ''
        },
    },
    yaxis: {
        labels: {
            formatter: nFormatter,
        }
    },
    markers: {
        size: 5,
        hover: {
            sizeOffset: 2
        }
    },
    dataLabels: {
        enabled: false
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
        show: false
    },
    noData: {
        text: '',
        align: 'center',
        verticalAlign: 'middle',
        style: {
            color: '#fff',
            fontSize: '16px',
        }
    }
}

export default {
    name: 'WidgetArea',
    components: {
        VueApexCharts
    },
    props: {
        series: {
            type: Array,
            required: true
        },
        options: {
            type: Object,
            required: false,
            default: () => ({})
        },
        type: {
            type: String,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        subtitle: {
            type: String,
            required: false
        },
        period: {
            type: String,
            required: true
        },
        modify: {
            type: String,
            required: false
        }
    },
    computed: {
        /**
         * Общие опции
         *
         * @return {Object}
         */
        generalOptions() {
            const whiteColors = this.series.map(() => '#fff');
            const stroke = {
                show:   true,
                width:  2,
                colors: [...whiteColors],
            };
            const fill = {
                opacity: 0.3,
                type:    'solid',
                colors:  [...whiteColors],
            };
            const colors = this.series.map(({ ncColor }) => ncColor);
            const options = {
                ...DEFAULT_OPTIONS,
                colors,
                stroke,
                fill,
                ...this.options
            };

            options.noData.text = this.$i18n.t('noDataForPeriod');

            return options;
        }
    }
}
</script>
