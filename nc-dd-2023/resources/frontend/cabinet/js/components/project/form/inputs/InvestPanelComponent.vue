<template>
    <table v-if="hasData" class="table table--primary mb-16">
        <thead>
            <tr>
                <td colspan="3">
                    Суммы которые вам необходимо распределить между собственными средствами или привлекаемыми:
                </td>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td></td>
                <td><b>Распределенная сумма</b></td>
                <td style="width: 380px;"><b>Всего инвестиций</b></td>
            </tr>

            <tr
                v-if="field.data.smr"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('smr') }"
            >
                <td>Перечень видов работ СМР, капитального ремонта:</td>
                <td>{{ numberFormat(field.distributed.smr) }}</td>
                <td>{{ numberFormat(field.data.smr) }}</td>
            </tr>

            <tr
                v-if="field.data.overhaul"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('overhaul') }"
            >
                <td>Перечень материалов при СМР, капитальном ремонте:</td>
                <td>{{ numberFormat(field.distributed.overhaul) }}</td>
                <td>{{ numberFormat(field.data.overhaul) }}</td>
            </tr>

            <tr
                v-if="field.data.readyPremises"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('readyPremises') }"
            >
                <td>Помещения, здания, сооружения:</td>
                <td>{{ numberFormat(field.distributed.readyPremises) }}</td>
                <td>{{ numberFormat(field.data.readyPremises) }}</td>
            </tr>

            <tr
                v-if="field.data.equipment"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('equipment') }"
            >
                <td>Приобретение оборудования, техники, мебели:</td>
                <td>{{ numberFormat(field.distributed.equipment) }}</td>
                <td>{{ numberFormat(field.data.equipment) }}</td>
            </tr>

            <tr
                v-if="field.data.raw"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('raw') }"
            >
                <td>Закуп сырья, товаров, расходных материалов:</td>
                <td>{{ numberFormat(field.distributed.raw) }}</td>
                <td>
                    <span style="display: inline-block; min-width: 90px;">
                        {{ numberFormat(field.data.raw) }}
                    </span>
                    ×
                    <VueNumber
                        v-model="raw"
                        v-bind="{...numberConfig, max: 120, suffix: ' мес.'}"
                        @input="({ target }) => raw = target.unmasked"
                        style="width: 80px; height: 32px;"
                    />
                    = {{ rawTotal }}
                </td>
            </tr>

            <tr
                v-if="field.data.wages"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('wages') }"
            >
                <td>Расходы по выплате заработной платы:</td>
                <td>{{ numberFormat(field.distributed.wages) }}</td>
                <td>
                    <span style="display: inline-block; min-width: 90px;">
                        {{ numberFormat(field.data.wages) }}
                    </span>
                    ×
                    <VueNumber
                        v-model="wages"
                        v-bind="{...numberConfig, max: 120, suffix: ' мес.'}"
                        @input="({ target }) => wages = target.unmasked"
                        style="width: 80px; height: 32px;"
                    />
                    = {{ wagesTotal }}
                </td>
            </tr>

            <tr
                v-if="field.data.advertising"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('advertising') }"
            >
                <td>Расходы на рекламу:</td>
                <td>{{ numberFormat(field.distributed.advertising) }}</td>
                <td>
                    <span style="display: inline-block; min-width: 90px;">
                        {{ numberFormat(field.data.advertising) }}
                    </span>
                    ×
                    <VueNumber
                        v-model="advertising"
                        v-bind="{...numberConfig, max: 120, suffix: ' мес.'}"
                        @input="({ target }) => advertising = target.unmasked"
                        style="width: 80px; height: 32px;"
                    />
                    = {{ advertisingTotal }}
                </td>
            </tr>

            <tr
                v-if="field.data.rentalExpenses"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('rentalExpenses') }"
            >
                <td>Расходы по аренде:</td>
                <td>{{ numberFormat(field.distributed.rentalExpenses) }}</td>
                <td>
                    <span style="display: inline-block; min-width: 90px;">
                        {{ numberFormat(field.data.rentalExpenses) }}
                    </span>
                    ×
                    <VueNumber
                        v-model="rentalExpenses"
                        v-bind="{...numberConfig, max: 120, suffix: ' мес.'}"
                        @input="({ target }) => rentalExpenses = target.unmasked"
                        style="width: 80px; height: 32px;"
                    />
                    = {{ rentalExpensesTotal }}
                </td>
            </tr>

            <tr
                v-if="field.data.otherExpenses"
                :class="{ 'bg-danger': errors[name + '.value'] && errors[name + '.value'].includes('otherExpenses') }"
            >
                <td>Прочие расходы:</td>
                <td>{{ numberFormat(field.distributed.otherExpenses) }}</td>
                <td>
                    <span style="display: inline-block; min-width: 90px;">
                        {{ numberFormat(field.data.otherExpenses) }}
                    </span>
                    ×
                    <VueNumber
                        v-model="otherExpenses"
                        v-bind="{...numberConfig, max: 120, suffix: ' мес.'}"
                        @input="({ target }) => otherExpenses = target.unmasked"
                        style="width: 80px; height: 32px;"
                    />
                    = {{ otherExpensesTotal }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';

export default {
    name:   'InvestPanelComponent',
    mixins: [projectFormMixin],
    props:  {
        name:   {
            type: String,
        },
        field:  {
            type: Object,
        },
        errors: {
            type: Object,
        },
    },
    data() {
        return {
            raw:            1,
            wages:          1,
            advertising:    1,
            rentalExpenses: 1,
            otherExpenses:  1,
        };
    },
    computed: {
        /**
         * @return {*|null}
         */
        hasData() {
            return this.field.data.smr ||
                this.field.data.overhaul ||
                this.field.data.readyPremises ||
                this.field.data.equipment ||
                this.field.data.raw ||
                this.field.data.wages ||
                this.field.data.advertising ||
                this.field.data.rentalExpenses ||
                this.field.data.otherExpenses;
        },

        /**
         * @return {string|null}
         */
        rawTotal() {
            return this.raw ? this.numberFormat(this.field.data.raw * this.raw) : null;
        },

        /**
         * @return {string|null}
         */
        wagesTotal() {
            return this.wages ? this.numberFormat(this.field.data.wages * this.wages) : null;
        },

        /**
         * @return {string|null}
         */
        advertisingTotal() {
            return this.advertising ? this.numberFormat(this.field.data.advertising * this.advertising) : null;
        },

        /**
         * @return {string|null}
         */
        rentalExpensesTotal() {
            return this.rentalExpenses ? this.numberFormat(this.field.data.rentalExpenses * this.rentalExpenses) : null;
        },

        /**
         * @return {string|null}
         */
        otherExpensesTotal() {
            return this.otherExpenses ? this.numberFormat(this.field.data.otherExpenses * this.otherExpenses) : null;
        },
    },
};
</script>
