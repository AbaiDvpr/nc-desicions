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
    },
}

export default {
    name: 'WidgetDonutTinyExt',
    components: {
        VueApexCharts
    },
    props: {
        data: {
            type: Object,
            required: true
        },
        options: {
            type: Object,
            required: false,
            default: () => ({})
        },
        cost: {
            type: String,
            required: true
        },
        title: {
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
        const amount = this.data.reduce((acc, item) => acc + Math.abs(item.value), 0);
        const tooltip = {
            shared: false,
            enabled: Boolean(this.data.length),
            y: {
                formatter: (value, { seriesIndex }) => {
                    value = this.data[seriesIndex]['value'];

                    return `${Number(value).toLocaleString(['ru'])} ₸`;
                }
            },
        };
        let labels = [];
        let colors = [];
        let series = [];

        this.data.map(({ title, color, value }) => {
            labels.push(title);
            colors.push(color);
            series.push(parseFloat(((Math.abs(value) / amount) * 100).toFixed(0)));
        });

        if (!this.data.length) {
            series = [100];
            colors = ['#7B809A'];
        }

        return {
            series,
            generalOptions: {
                ...DEFAULT_OPTIONS,
                ...this.options,
                colors,
                labels,
                tooltip
            }
        }
    }
}
</script>
