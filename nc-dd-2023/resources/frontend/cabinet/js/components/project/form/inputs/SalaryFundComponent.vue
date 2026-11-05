<template>
    <div class="wrap">
        <div class="form-group">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div v-if="field.placeholder" class="form-group__subtitle">
                {{ field.placeholder }}
            </div>

            <div v-if="errors[name + '.value']" class="form-group__error">
                {{ errors[name + '.value'][0] }}
            </div>

            <div v-if="field.inputs" class="table-wrap">
                <table class="table table--primary">
                    <thead>
                        <tr>
                            <th v-if="field.withInvest" style="width: 98px;">На инвест период</th>

                            <th>{{ field.defaultInput['name']['title'] }}</th>

                            <th>{{ field.defaultInput['count']['title'] }}</th>

                            <th>{{ field.defaultInput['salaryPerMonth']['title'] }}</th>

                            <th>{{ field.defaultInput['percent']['title'] }}</th>

                            <th>{{ field.defaultInput['industrial']['title'] }}</th>

                            <th style="min-width: 200px;">{{ field.defaultInput['services']['title'] }}</th>

                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(value, index) in field.inputs">
                            <td v-if="field.withInvest" style="width: 98px;">
                                <input type="checkbox" :checked="value.invest" disabled>
                            </td>
                            <td>{{ value.name }}</td>
                            <td class="text-center">{{ numberFormat(value.count) }}</td>

                            <td class="text-center">{{ numberFormat(value.salaryPerMonth) }}</td>

                            <td class="text-center">{{ value.percent ? value.percent + ' %' : '' }}</td>

                            <td class="text-center" style="min-width: 100px;">{{ field.defaultInput.industrial.values[value.industrial] }}</td>

                            <td style="min-width: 200px;">{{ getServiceList(value.services) }}</td>

                            <td style="width: 102px;">
                                <div class="btn-group btn-group--center btn-group--no-wrap">
                                    <button
                                        class="btn btn--danger btn--size-sm"
                                        type="button"
                                        title="Удалить"
                                        @click="deleteField(index, $event)"
                                    >
                                        <AppIcon name="trash" />
                                    </button>

                                    <button
                                        class="btn btn--primary btn--size-sm"
                                        type="button"
                                        title="Редактировать"
                                        @click="editField(index, $event)"
                                    >
                                        <AppIcon name="edit" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td v-if="field.withInvest">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.invest.value'] }]">
                                    <input
                                        v-model="field.defaultInput.invest.value"
                                        type="checkbox"
                                        class="input"
                                    >

                                    <span
                                        v-if="salaryFundErrors['defaultInput.invest']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.invest'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.name.value'] }]">
                                    <input
                                        v-model="field.defaultInput.name.value"
                                        type="text"
                                        class="input"
                                    >

                                    <span
                                        v-if="salaryFundErrors['defaultInput.name.value']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.name.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.count.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.count.value"
                                        v-bind="numberConfig"
                                        class="input"
                                        @input="({ target }) => field.defaultInput.count.value = target.unmasked"
                                    />

                                    <span
                                        v-if="salaryFundErrors['defaultInput.count.value']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.count.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.salaryPerMonth.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.salaryPerMonth.value"
                                        v-bind="numberConfig"
                                        class="input"
                                        @input="({ target }) => field.defaultInput.salaryPerMonth.value = target.unmasked"
                                    />

                                    <span
                                        v-if="salaryFundErrors['defaultInput.salaryPerMonth.value']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.salaryPerMonth.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.percent.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.percent.value"
                                        v-bind="percentConfig"
                                        class="input"
                                        @input="({ target }) => field.defaultInput.percent.value = target.unmasked"
                                    />

                                    <span
                                        v-if="salaryFundErrors['defaultInput.percent.value']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.percent.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.industrial.value'] }]">
                                    <select
                                        v-model="field.defaultInput.industrial.value"
                                        class="select"
                                    >
                                        <option
                                            v-for="(value, key) in field.defaultInput.industrial.values"
                                            :value="key"
                                        >{{ value }}</option>
                                    </select>

                                    <span
                                        v-if="salaryFundErrors['defaultInput.industrial.value']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.industrial.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : salaryFundErrors['defaultInput.services.value'] }]">
                                    <VueMultiselect
                                        v-model="field.defaultInput.services.value"
                                        :options="field.defaultInput.services.values"
                                        :multiple="true"
                                        :close-on-select="false"
                                        :clear-on-select="false"
                                        :preserve-search="false"
                                        placeholder=""
                                        :select-label="''"
                                        :selected-label="''"
                                        :deselect-label="''"
                                        :searchable="false"
                                        label="name"
                                        track-by="id"
                                        :preselect-first="false"
                                    >
                                        <template #selection="{ values }">
                                            <span>{{ values.length ? 'Выбрано ' + values.length : '' }}</span>
                                        </template>
                                    </VueMultiselect>

                                    <span
                                        v-if="salaryFundErrors['defaultInput.services.value']"
                                        class="form-group__error"
                                    >{{ salaryFundErrors['defaultInput.services.value'][0] }}</span>
                                </div>
                            </td>

                            <td style="width: 102px;">
                                <div class="btn-group btn-group--center btn-group--no-wrap">
                                    <button
                                        type="button"
                                        class="btn btn--info btn--size-sm"
                                        title="Очистить"
                                        @click="clearForm"
                                    >
                                        <AppIcon name="erase" />
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn--primary btn--size-sm"
                                        title="Сохранить"
                                        @click="saveField"
                                    >
                                        <AppIcon name="save" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" colspan="7">
                                <b>Общая сумма затрат на фонд зп:</b> {{ numberFormat(totalSum) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import axios from '@/api/axios';
import projectFormMixin from '@/mixins/projectFormMixin';
import VueMultiselect from 'vue-multiselect';
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'SalaryFundComponent',
    mixins: [projectFormMixin],
    components: {
        VueMultiselect,
        FormHintButton
    },
    props: {
        name: {
            type: String
        },
        field: {
            type: Object
        },
        errors: {
            type: Object
        }
    },
    data() {
        return {
            salaryFundErrors: {}
        };
    },
    computed: {
        /**
         * Общая сумма
         *
         * @returns {Number}
         */
        totalSum() {
            return this.field.inputs.reduce((sum, item) => {
                const itemCount = parseFloat(item.count);
                const itemPricePerMonth = parseFloat(item.salaryPerMonth || 0);
                return sum + (itemCount * itemPricePerMonth);
            }, 0);
        },
    },
    watch: {
        'field.inputs': function (inputs) {
            this.field.value = inputs.length
        }
    },
    methods: {
        /**
         * Очищаем или заполняем форму
         */
        clearOrFillInTheField(index) {
            const id = index !== null ? this.field.inputs[index].id : null;

            for (const key of Object.keys(this.field.defaultInput)) {
                if (key === 'services') {
                    this.field.defaultInput[key]['value'] = id !== null
                        ? this.getSelectedServices(this.field.inputs[index][key])
                        : [];
                } else {
                    this.field.defaultInput[key]['value'] = id !== null
                        ? this.field.inputs[index][key]
                        : null;
                }
            }
        },

        /**
         * Редактируем элемент
         */
        deleteField(index, e) {
            e.preventDefault();

            let self = this;
            let id   = self.field.inputs[index]['id'];

            self.clearForm();
            this.setLoading(true);

            axios.put(this.getBaseUrl() + '/salary-fund/' + id)
                .then(() => self.field.inputs.splice(index, 1))
                .finally(() => this.setLoading(false));
        },

        /**
         * Редактируем элемент
         */
        editField(index, e) {
            e.preventDefault();

            this.clearOrFillInTheField(index);
        },

        /**
         * Очистка заполненной формы
         */
        clearForm() {
            this.clearOrFillInTheField(null);
        },

        /**
         * Сохраняем заполненную форму
         */
        saveField() {
            this.salaryFundErrors = {};
            this.setLoading(true);

            axios.post(this.getBaseUrl() + '/salary-fund', this.field)
                .then(response => {
                    this.field.inputs = response.data;
                    this.clearOrFillInTheField(null);
                })
                .catch(error => this.salaryFundErrors = error.response.data.errors)
                .finally(() => this.setLoading(false));
        },

        /**
         * Выводим список услуг
         */
        getServiceList(data) {
            const services = this.field.defaultInput.services.values;

            if (data !== null && data.length && services.length) {
                return Object.values(data).map(item => {
                    const value = services.find(service => service.id === item);

                    return value?.name || '';
                }).join(', ');
            }

            return '';
        },

        /**
         * Выводим список выбранных услуг
         */
        getSelectedServices(data) {
            const services = this.field.defaultInput.services.values;

            if (data && services.length) {
                return Object.values(data).map(item => {
                    return services.find(service => service.id === item);
                });
            }

            return [];
        }
    }
}
</script>
