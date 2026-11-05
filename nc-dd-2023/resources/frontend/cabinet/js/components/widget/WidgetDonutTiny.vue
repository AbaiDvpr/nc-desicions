<template>
    <div :class="['widget widget-donut-tiny', modify]">
        <div class="widget__chart widget-donut-tiny__chart">
            <VueApexCharts
                type="donut"
                width="100%"
                height="auto"
                :series="series"
                :options="generalOptions"
            />
        </div>
        <div class="widget__info">
            <h2 class="widget-donut-tiny__title">{{ title }}</h2>

            <span class="widget-donut-tiny__cost">{{ cost }}</span>
        </div>
    </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';

/**
 * @type {Object}
 */
const DEFAULT_OPTIONS = {
    chart: {
        fontFamily: 'Arial, sans-serif'
    },
    dataLabels: {
        enabled: false
    },
    legend: {
        show: false
    },
    plotOptions: {
        pie: {
            donut: {
                size: '75%'
            }
        }
    }
}

export default {
    name: 'WidgetDonutTiny',
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
        title: {
            type: String,
            required: true
        },
        cost: {
            type: String,
            required: true
        },
        modify: {
            type: String,
            required: false,
            default: ''
        }
    },
    data() {
        return {
            generalOptions: {
                ...DEFAULT_OPTIONS,
                ...this.options
            }
        }
    }
}
</script>
