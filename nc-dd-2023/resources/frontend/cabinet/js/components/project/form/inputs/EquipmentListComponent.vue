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
            >{{ field.placeholder }}</div>

            <div v-if="errors[name + '.value']" class="form-group__error">
                {{ errors[name + '.value'][0] }}
            </div>

            <div v-if="field.inputs" class="table-wrap">
                <table class="table table--primary">
                    <thead>
                        <tr>
                            <th>{{ field.defaultInput['name']['title'] }}</th>

                            <th>{{ field.defaultInput['count']['title'] }}</th>

                            <th>{{ field.defaultInput['priceOneUnit']['title'] }}</th>

                            <th>{{ field.defaultInput['year']['title'] }}</th>

                            <th>{{ field.defaultInput['lifeTime']['title'] }}</th>

                            <th>{{ field.defaultInput['price']['title'] }}</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(value, index) in field.inputs">
                            <td>{{ value.name }}</td>
                            <td class="text-center">{{ numberFormat(value.count) }}</td>

                            <td class="text-center">{{ numberFormat(value.priceOneUnit) }}</td>

                            <td class="text-center">{{ value.year }}</td>

                            <td class="text-center">{{ value.lifeTime }}</td>

                            <td class="text-right">{{ numberFormat(value.price) }}</td>

                            <td style="width: 102px;">
                                <div class="btn-group btn-group--center btn-group--no-wrap">
                                    <button
                                        type="button"
                                        class="btn btn--danger btn--size-sm"
                                        @click="deleteField(index, $event)"
                                        title="Удалить"
                                    >
                                        <AppIcon name="trash" />
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn--primary btn--size-sm"
                                        @click="editField(index, $event)"
                                        title="Редактировать"
                                    >
                                        <AppIcon name="edit" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : equipmentErrors['defaultInput.name.value'] }]">
                                    <input
                                        type="text" v-model="field.defaultInput.name.value"
                                        class="input"
                                    >
                                    <span
                                        class="form-group__error"
                                        v-if="equipmentErrors['defaultInput.name.value']"
                                    >{{ equipmentErrors['defaultInput.name.value'][0] }}</span>
                                </div>
                            </td>
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : equipmentErrors['defaultInput.count.value'] }]">
                                    <input
                                        v-model="field.defaultInput.count.value"
                                        class="input"
                                        type="text"
                                    >

                                    <span
                                        v-if="equipmentErrors['defaultInput.count.value']"
                                        class="form-group__error"
                                    >{{ equipmentErrors['defaultInput.count.value'][0] }}</span>
                                </div>
                            </td>
                            <td class="align-top">
                                <div :class="['form-group', { 'is-error': equipmentErrors['defaultInput.priceOneUnit.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.priceOneUnit.value"
                                        v-bind="numberConfig"
                                        class="input"
                                        @input="({ target }) => field.defaultInput.priceOneUnit.value = target.unmasked"
                                    />

                                    <span
                                        v-if="equipmentErrors['defaultInput.priceOneUnit.value']"
                                        class="form-group__error"
                                    >{{ equipmentErrors['defaultInput.priceOneUnit.value'][0] }}</span>
                                </div>
                            </td>
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : equipmentErrors['defaultInput.year.value'] }]">
                                    <input
                                        type="text" v-model="field.defaultInput.year.value"
                                        class="input"
                                    >
                                    <span
                                        class="form-group__error"
                                        v-if="equipmentErrors['defaultInput.year.value']"
                                    >{{ equipmentErrors['defaultInput.year.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : equipmentErrors['defaultInput.lifeTime.value'] }]">
                                    <input
                                        type="text" v-model="field.defaultInput.lifeTime.value"
                                        class="input"
                                    >

                                    <span
                                        class="form-group__error"
                                        v-if="equipmentErrors['defaultInput.lifeTime.value']"
                                    >{{ equipmentErrors['defaultInput.lifeTime.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : equipmentErrors['defaultInput.price.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.price.value"
                                        v-bind="numberConfig"
                                        class="input"
                                        :disabled="true"
                                        @input="({ target }) => field.defaultInput.price.value = target.unmasked"
                                    />

                                    <span
                                        v-if="equipmentErrors['defaultInput.price.value']"
                                        class="form-group__error"
                                    >{{ equipmentErrors['defaultInput.price.value'][0] }}</span>
                                </div>
                            </td>

                            <td style="width: 102px;">
                                <div class="btn-group btn-group--center btn-group--no-wrap">
                                    <button
                                        type="button"
                                        class="btn btn--info btn--size-sm"
                                        @click="clearForm" title="Очистить"
                                    >
                                        <AppIcon name="erase" />
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn--primary btn--size-sm"
                                        @click="saveField" title="Сохранить"
                                    >
                                        <AppIcon name="save" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" colspan="7">
                                <b>Общая сумма:</b> {{ numberFormat(totalSum) }}
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
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'EquipmentListComponent',
    mixins: [projectFormMixin],
    components: {
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
            equipmentErrors: {},
        }
    },
    computed: {
        /**
         * Общая сумма
         *
         * @returns {Number}
         */
        totalSum() {
            return this.field.inputs.reduce((sum, item) => sum + parseFloat(item.price || 0), 0);
        },
    },
    watch: {
        'field.defaultInput.count.value': function () {
            this.getDefaultInputPrice();
        },
        'field.defaultInput.priceOneUnit.value': function () {
            this.getDefaultInputPrice();
        }
    },
    methods: {
        /**
         * Очищаем или заполняем форму
         */
        clearOrFillInTheField(index) {
            let self = this;
            let id   = (index !== null) ? self.field.inputs[index].id : null;

            for (const [key, value] of Object.entries(self.field.defaultInput)) {
                self.field.defaultInput[key]['value'] = (id !== null) ?
                    self.field.inputs[index][key] : null;
            }
        },

        getDefaultInputPrice() {
            let $count = this.field.defaultInput.count.value ?? '';

            $count = Number.isInteger($count) ? $count : $count.replace(/[^\d]/g, '');
            $count = Number.isNaN(parseInt($count)) ? 0 : parseInt($count);

            let $priceOneUnit = this.field.defaultInput.priceOneUnit.value ?? '';

            $priceOneUnit = Number.isInteger($priceOneUnit) ?
                $priceOneUnit :
                $priceOneUnit.replace(/[^\d]/g, '');
            $priceOneUnit = Number.isNaN(parseInt($priceOneUnit)) ? 0 : parseInt($priceOneUnit);

            this.field.defaultInput.price.value = $count * $priceOneUnit;
        },

        /**
         * Редактируем элемент
         */
        deleteField(index, e) {
            e.preventDefault();

            const id = this.field.inputs[index]['id'];

            this.clearForm();
            this.setLoading(true);

            axios.put(this.getBaseUrl() + '/equipment-list/' + id)
                .then(() => {
                    this.field.inputs.splice(index, 1);
                })
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
            this.equipmentErrors = {};
        },

        /**
         * Сохраняем заполненную форму
         */
        saveField() {
            this.equipmentErrors = {};
            this.setLoading(true);

            axios.post(this.getBaseUrl() + '/equipment-list', this.field)
                .then(response => {
                    this.field.inputs = response.data;
                    this.clearOrFillInTheField(null);
                })
                .catch(error => this.equipmentErrors = error.response.data.errors)
                .finally(() => this.setLoading(false));

        }
    }
}
</script>
