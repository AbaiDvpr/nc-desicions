<template>
    <div class="widget">
        <div :class="{'widget__chart widget__chart--mix': true, [`widget__chart--${type}`]: type}">
            <VueApexCharts
                type="line"
                height="100%"
                :series="series"
                :options="generalOptions"
            />
        </div>

        <h2 class="widget__title">{{ title }}</h2>

        <p class="widget__subtitle">{{ subtitle }}</p>

        <hr class="widget__split">

        <div class="widget__period">
            {{ $t('forPeriod') }}:
            <strong>{{ period }}</strong>
        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";
import {convertHexToRGBA} from "@/helpers/helpers";

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
        borderColor: convertHexToRGBA('#ffffff', 0.5),
        strokeDashArray: 10,
        position: 'back',
        xaxis: {
            lines: {
                show: true
            }
        },
        yaxis: {
            lines: {
                show: true
            }
        },
        padding: {
            top: 0,
            right: 8,
            bottom: 0,
            left: 8
        }
    },
    fill: {
        opacity: 0.5,
    },
    dataLabels: {
        enabled: false
    },
    tooltip: {
        enabled: true
    }
}

export default {
    name: 'WidgetBarLine',
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
            required: true
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
            const stroke = {
                show: true,
                width: 2,
                colors: this.series.map(() => '#fff')
            };
            const colors = this.series.map(({ ncColor }) => ncColor);

            return {
                ...DEFAULT_OPTIONS,
                colors,
                stroke,
                ...this.options,
            }
        }
    },
}
</script>
