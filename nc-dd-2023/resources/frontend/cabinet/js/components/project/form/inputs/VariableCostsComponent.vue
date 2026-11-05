<template>
    <div class="wrap">
        <div class="form-group">
            <div class="form-group__label">
                <span>{{ field.title }}</span>

                <FormHintButton :message="field.hint" />
            </div>

            <div
                v-if="field.placeholder"
                class="form-group__subtitle"
                v-html="field.placeholder"
            ></div>

            <template v-if="field.value">
                <div class="form-group__label">
                    <input id="callback-cb" type="checkbox" v-model="field.value" class="mr-10" style="width: auto;">

                    <label for="callback-cb">На единицу продукции</label>
                </div>

                <div v-if="Object.keys(field.inputs).length" class="table-wrap">
                    <table class="table table--primary table-wrap--striped-body">
                        <thead>
                        <tr>
                            <th>
                                {{ field.defaultInput['vs_id']['title'] }}
                            </th>

                            <th>
                                {{ field.defaultInput['costType']['title'] }}
                            </th>

                            <th>
                                {{ field.defaultInput['price']['title'] }}
                            </th>

                            <th></th>
                        </tr>
                        </thead>

                        <tbody v-for="(input, categoryId) in field.inputs">
                        <tr>
                            <td
                                :rowspan="Object.keys(input.items).length + 1"
                                :colspan="Object.keys(input.items).length > 0 ? 1 : 4"
                            >
                                <b>{{ input.title }}</b> <br/>
                                <span>Цена за ед./ср.чек: <b>{{  numberFormat(input.price) }}</b></span>
                            </td>
                        </tr>

                        <tr v-for="(item, index) in input.items">
                            <td>{{ item.costType }}</td>

                            <td class="text-right">{{ numberFormat(item.price) }}</td>

                            <td style="width: 102px;">
                                <div class="btn-group btn-group--center btn-group--no-wrap">
                                    <button
                                        class="btn btn--danger btn--size-sm"
                                        type="button"
                                        title="Удалить"
                                        @click="deleteField(index, categoryId, $event)"
                                    >
                                        <AppIcon name="trash" />
                                    </button>

                                    <button
                                        class="btn btn--primary btn--size-sm"
                                        type="button"
                                        title="Редактировать"
                                        @click="editField(index, categoryId, $event)"
                                    >
                                        <AppIcon name="edit" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>

                        <tbody>
                        <tr>
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : variableCostsErrors['defaultInput.vs_id.value'] }]">
                                    <select
                                        v-model="field.defaultInput.vs_id.value"
                                        class="select"
                                    >
                                        <option
                                            v-for="(input, key) in field.inputs"
                                            :value="key"
                                            :selected="key === field.defaultInput.vs_id.value ? 'selected' : false"
                                        >{{ input.title }}</option>
                                    </select>

                                    <span
                                        v-if="variableCostsErrors['defaultInput.vs_id.value']"
                                        class="form-group__error"
                                    >{{ variableCostsErrors['defaultInput.vs_id.value'][0] }}</span>
                                </div>
                            </td>
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : variableCostsErrors['defaultInput.costType.value'] }]">
                                    <input
                                        v-model="field.defaultInput.costType.value"
                                        type="text"
                                        class="input"
                                    >

                                    <span
                                        v-if="variableCostsErrors['defaultInput.costType.value']"
                                        class="form-group__error"
                                    >{{ variableCostsErrors['defaultInput.costType.value'][0] }}</span>
                                </div>
                            </td>
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : variableCostsErrors['defaultInput.price.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.price.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => field.defaultInput.price.value = target.unmasked"
                                    />

                                    <span
                                        v-if="variableCostsErrors['defaultInput.price.value']"
                                        class="form-group__error"
                                    >{{ variableCostsErrors['defaultInput.price.value'][0] }}</span>
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
                            <td class="text-right" colspan="4">
                                <b>Общая стоимость затрат:</b> {{ numberFormat(totalSum) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <template v-else>
                <div class="form-group__label">
                    <input id="callback-cb" type="checkbox" v-model="field.value" class="mr-10" style="width: auto;">

                    <label for="callback-cb">Переменные расходы</label>
                </div>

                <VariableCostsOtherComponent
                    :name="field.variableCostsOther.name"
                    :errors="errors"
                    :field="field.variableCostsOther"
                />
            </template>
        </div>
    </div>
</template>

<script>
import axios from '@/api/axios';
import projectFormMixin from '@/mixins/projectFormMixin';
import FormHintButton from '@/components/form/FormHintButton';
import VariableCostsOtherComponent from '@/components/project/form/inputs/VariableCostsOtherComponent';

export default {
    name: 'VariableCostsComponent',
    mixins: [projectFormMixin],
    components: {
        FormHintButton,
        VariableCostsOtherComponent
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
            variableCostsErrors: {}
        }
    },
    computed: {
        /**
         * Общая сумма
         *
         * @returns {Number}
         */
        totalSum() {
            return Object.values(this.field.inputs)
                .flatMap(input => input.items.map(item => parseFloat(item.price || 0)))
                .reduce((sum, price) => sum + price, 0);
        }
    },
    methods: {
        /**
         * Очищаем или заполняем форму
         */
        clearOrFillInTheField(index = null, categoryId = null) {
            let self         = this;
            let defaultInput = self.field.defaultInput;
            let item         = [];
            let id           = null;

            if (categoryId !== null) {
                item = self.field.inputs[categoryId]['items'][index];
                id   = item.id;
            }

            for (let [key, value] of Object.entries(defaultInput)) {
                defaultInput[key]['value'] = (id !== null) ? item[key] : null;
            }
        },

        /**
         * Редактируем элемент
         */
        deleteField(index, categoryId, e) {
            e.preventDefault();

            let id = this.field.inputs[categoryId]['items'][index]['id'];

            this.clearForm();
            this.setLoading(true);

            axios.put(this.getBaseUrl() + '/variable-costs/' + id)
                .then(() => this.field.inputs[categoryId]['items'].splice(index, 1))
                .finally(() => this.setLoading(false));
        },

        /**
         * Редактируем элемент
         */
        editField(index, categoryId, e) {
            e.preventDefault();
            this.clearOrFillInTheField(index, categoryId);
        },

        /**
         * Очистка заполненной формы
         */
        clearForm() {
            this.clearOrFillInTheField();
        },

        /**
         * Сохраняем заполненную форму
         */
        saveField() {
            this.variableCostsErrors = {};
            this.setLoading(true);

            axios.post(this.getBaseUrl() + '/variable-costs', this.field)
                .then(response => {
                    this.field.inputs = response.data;
                    this.clearOrFillInTheField();
                })
                .catch(error => this.variableCostsErrors = error.response.data.errors)
                .finally(() => this.setLoading(false));
        }
    }
}
</script>
