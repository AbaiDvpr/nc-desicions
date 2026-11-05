<template>
    <div class="wrap">
        <div class="form-group">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div
                v-if="errors[name + '.inputs']"
                class="form-group__error"
            >{{ errors[name + '.inputs'][0] }}</div>

            <div v-if="field.inputs" class="table-wrap">
                <table class="table table--primary">
                    <thead>
                        <tr>
                            <th class="text-center">№</th>

                            <th v-if="field.withInvest" style="width: 98px;">На инвест период</th>

                            <th>Наименование затрат</th>

                            <th>Затраты за месяц</th>

                            <th>Период</th>

                            <th style="width: 130px;">Тип</th>

                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(input, index) in field.inputs">
                            <td class="text-center">{{ index + 1}}</td>

                            <td v-if="field.withInvest" style="width: 98px;">
                                <div :class="['form-group', {'is-error' : errors[name + '.inputs.'+index+'.invest']}]">
                                    <input
                                        v-model="input.invest"
                                        type="checkbox"
                                        :checked="input.invest"
                                    >

                                    <span
                                        v-if="errors[name + '.inputs.'+index+'.invest']"
                                        class="form-group__error"
                                    >{{ errors[name + '.inputs.'+index+'.invest'][0] }}</span>
                                </div>
                            </td>

                            <td>
                                <div :class="['form-group', {'is-error' : errors[name + '.inputs.'+index+'.name'] }]">
                                    <input
                                        v-model="input.name"
                                        type="text"
                                        class="input"
                                    >

                                    <span
                                        v-if="errors[name + '.inputs.'+index+'.name']"
                                        class="form-group__error"
                                    >{{ errors[name + '.inputs.'+index+'.name'][0] }}</span>
                                </div>
                            </td>

                            <td>
                                <div :class="['form-group', {'is-error' : errors[name + '.inputs.'+index+'.value'] }]">
                                    <VueNumber
                                        v-model="input.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => input.value = target.unmasked"
                                    />

                                    <span
                                        v-if="errors[name + '.inputs.'+index+'.value']"
                                        class="form-group__error"
                                    >{{ errors[name + '.inputs.'+index+'.value'][0] }}</span>
                                </div>
                            </td>

                            <td>
                                <div :class="['form-group', {'is-error' : errors[name + '.inputs.' + index + '.period'] }]">
                                    <select v-model.number="input.period" class="select">
                                        <option
                                            v-for="(value, key) in field.periods"
                                            :value="key"
                                        >{{ value }}</option>
                                    </select>
                                </div>
                            </td>

                            <td>
                                <div :class="['form-group', {'is-error' : errors[name + '.inputs.' + index + '.type'] }]">
                                    <select v-model.number="input.typeExpense" class="select">
                                        <option
                                            v-for="(value, key) in field.typesExpenses"
                                            :value="key"
                                        >{{ value }}</option>
                                    </select>
                                </div>
                            </td>

                            <td style="width: 52px;">
                                <div class="btn-group btn-group--center">
                                    <button
                                        class="btn btn--danger btn--size-sm"
                                        type="button"
                                        title="Удалить"
                                        @click="deleteField(index)"
                                    >
                                        <AppIcon name="trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td></td>

                            <td v-if="field.withInvest">
                                <div class="form-group">
                                    <input
                                        v-model="field.defaultInput.invest"
                                        type="checkbox"
                                        :checked="field.defaultInput.invest"
                                    >
                                </div>
                            </td>

                            <td class="align-top">
                                <div class="form-group">
                                    <input class="input" v-model="field.defaultInput.name" type="text">
                                </div>
                            </td>

                            <td class="align-top">
                                <div class="form-group">
                                    <VueNumber
                                        v-model="field.defaultInput.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => field.defaultInput.value = target.unmasked"
                                    />
                                </div>
                            </td>

                            <td class="align-top">
                                <div class="form-group">
                                    <select class="select" v-model.number="field.defaultInput.period">
                                        <option
                                            v-for="(value, key) in field.periods"
                                            :value="key"
                                        >{{ value }}</option>
                                    </select>
                                </div>
                            </td>

                            <td>
                                <div class="form-group">
                                    <select v-model.number="field.defaultInput.typeExpense" class="select">
                                        <option
                                            v-for="(value, key) in field.typesExpenses"
                                            :value="key"
                                        >{{ value }}</option>
                                    </select>
                                </div>
                            </td>

                            <td style="width: 52px;" :colspan="field.withInvest ? 2 : 0" >
                                <div class="btn-group btn-group--center">
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--size-sm"
                                        @click="saveField"
                                        title="Сохранить"
                                    >
                                        <AppIcon name="save" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" colspan="6">
                                <b>Общая сумма административных расходов:</b> {{ numberFormat(totalSum) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';
import TextComponent from './TextComponent';
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'AdministrativeExpensesComponent',
    mixins: [projectFormMixin],
    components: {
        TextComponent,
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
    computed: {
        /**
         * Общая сумма
         *
         * @returns {Number}
         */
        totalSum() {
            return this.field.inputs.reduce((sum, item) => sum + parseFloat(item.value || 0), 0);
        },
    },
    watch: {
        'field.inputs': function (inputs) {
            this.field.value = inputs.length
        }
    },
    methods: {
        /**
         * Редактируем элемент
         *
         * @param index
         */
        deleteField(index) {
            this.field.inputs.splice(index, 1);
        },

        /**
         * Сохраняем заполненную форму
         */
        saveField() {
            if (this.field.defaultInput.name && this.field.defaultInput.value) {
                this.field.inputs.push({...this.field.defaultInput});

                this.field.defaultInput.name        = null;
                this.field.defaultInput.typeExpense = 0;
                this.field.defaultInput.value       = null;
            }
        }
    }
}
</script>

