<template>
    <div v-if="isEmpty" class="panel">
        <div class="table-wrap">
            <table class="table table--primary table--sticky">
                <thead>
                <tr>
                    <td style="min-width: 320px;">{{ title }}</td>

                    <td
                        v-for="(period, periodKey) in data.periods"
                        :key="periodKey"
                        class="text-right"
                        v-text="getFormatDate(period, 'MMM YYYY')"
                        style="min-width: 110px;"
                    />
                </tr>
                </thead>

                <tbody>
                <tr
                    v-for="(report, reportKey) in data.data"
                    :key="reportKey"
                    :class="{ 'fw-600': report.isSelected }"
                >
                    <td v-if="report.isSelected">{{ report.name }}</td>

                    <td v-else>
                        &nbsp;&nbsp;&nbsp;
                        {{ report.name }}
                    </td>

                    <td v-for="({ value }) in report.data" class="text-right">
                        {{ formatTableNumber(value) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div v-else class="panel">
        <AppAlert type="info" :message="$t('noDataForPeriod')"/>
    </div>
</template>

<script>
import { getFormatDate } from '@/plugins/dayjs';

export default {
    name: 'FactPlanTable',
    props: {
        data: {
            type: Object,
            required: true,
            validator: ({ data, periods }) => data && periods
        },
        title: {
            type: String,
            default: ''
        }
    },
    computed: {
        /**
         * Проверка на пустоту
         *
         * @return {boolean}
         */
        isEmpty() {
            return Boolean(Object.keys(this.data).length && this.data.periods.length);
        }
    },
    methods: {
        /**
         * Возвращает отформатированные данные для ячеек
         *
         * @param   {number} value
         * @returns {string|null}
         */
        formatTableNumber(value) {
            return value ? this.numberFormat(value) : null;
        },

        getFormatDate
    }
}
</script>
