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

                            <th>{{ field.defaultInput['seasonal']['title'] }}</th>

                            <th>{{ field.defaultInput['price']['title'] }}</th>

                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(value, index) in field.inputs" :key="index" @dblclick="editField(index, $event)">
                            <td class="align-top">
                                <div
                                    v-if="activeIndex === index"
                                    :class="['form-group', {'is-error' : volumeSalesErrors['defaultInput.name.value']}]"
                                >
                                    <input
                                        v-model="field.defaultInput.name.value"
                                        type="text"
                                        class="input"
                                    >

                                    <span
                                        v-if="volumeSalesErrors['defaultInput.name.value']"
                                        class="form-group__error"
                                    >{{ volumeSalesErrors['defaultInput.name.value'][0] }}</span>
                                </div>

                                <div v-else>{{ value.name }}</div>
                            </td>

                            <td class="align-top">
                                <template v-if="activeIndex === index">
                                    <div :class="['form-input-group', {'is-error' : volumeSalesErrors['defaultInput.count.value'] }]">
                                        <div class="form-input-group__input">
                                            <VueNumber
                                                v-model="field.defaultInput.count.value"
                                                v-bind="numberConfig"
                                                @input="({ target }) => field.defaultInput.count.value = target.unmasked"
                                            />
                                        </div>

                                        <div class="form-input-group__append">
                                            <select class="select" v-model="field.defaultInput.units.value">
                                                <option
                                                    v-for="(val, key) in field.defaultInput.units.values"
                                                    :key="key"
                                                    :value="key"
                                                >{{ val }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <span
                                        v-if="volumeSalesErrors['defaultInput.count.value']"
                                        class="form-group__error"
                                    >{{ volumeSalesErrors['defaultInput.count.value'][0] }}</span>
                                </template>
                                <div v-else class="text-center">
                                    {{ numberFormat(value.count) }}
                                    {{ field.defaultInput.units.values[value.units] }}
                                </div>
                            </td>

                            <td class="align-top">
                                <template v-if="activeIndex === index">
                                    <div class="form-input-group">
                                        <div class="form-input-group__prepend">
                                            <input
                                                v-model="field.defaultInput.seasonal.isActive"
                                                type="checkbox"
                                                :checked="field.defaultInput.seasonal.isActive"
                                            >
                                        </div>

                                        <DatePicker
                                            v-model:value="field.defaultInput.seasonal.value"
                                            :class="['form-input-group__input', {'is-error': volumeSalesErrors['defaultInput.seasonal.value'] }]"
                                            popup-class="mx-datepicker-popup-hide-header"
                                            format="MMM"
                                            type="month"
                                            value-type="M"
                                            input-class="input"
                                            :multiple="true"
                                            :editable="false"
                                            :lang="locale"
                                            placeholder="сезонность"
                                            :disabled="!field.defaultInput.seasonal.isActive"
                                            @clear="field.defaultInput.seasonal.isActive = false"
                                        ></DatePicker>
                                    </div>

                                    <div
                                        v-if="volumeSalesErrors['defaultInput.seasonal.value']"
                                        class="form-group__error"
                                    >{{ volumeSalesErrors['defaultInput.seasonal.value'][0] }}</div>
                                </template>

                                <template v-else-if="value.seasonal && value.seasonal.length">
                                    <div class="text-center">{{ getMonthNames(value.seasonal) }}</div>
                                </template>

                                <template v-else>
                                    <div class="text-center">ежемесячно</div>
                                </template>
                            </td>

                            <td class="align-top">
                                <div v-if="activeIndex === index" :class="['form-group', {'is-error' : volumeSalesErrors['defaultInput.price.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.price.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => field.defaultInput.price.value = target.unmasked"
                                    />

                                    <span
                                        v-if="volumeSalesErrors['defaultInput.price.value']"
                                        class="form-group__error"
                                    >{{ volumeSalesErrors['defaultInput.price.value'][0] }}</span>
                                </div>
                                <div v-else class="text-right">
                                    {{ numberFormat(value.price) }}
                                </div>
                            </td>

                            <td style="width: 102px;">
                                <div class="btn-group btn-group--center btn-group--no-wrap">
                                    <template v-if="activeIndex !== index">
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
                                    </template>

                                    <template v-else>
                                        <button
                                            class="btn btn--info btn--size-sm"
                                            type="button"
                                            title="Очистить"
                                            @click="clearForm"
                                        >
                                            <AppIcon name="erase" />
                                        </button>

                                        <button
                                            class="btn btn--primary btn--size-sm"
                                            type="button"
                                            title="Сохранить"
                                            @click="saveField"
                                        >
                                            <AppIcon name="save" />
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="activeIndex === null">
                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : volumeSalesErrors['defaultInput.name.value'] }]">
                                    <input
                                        v-model="field.defaultInput.name.value"
                                        type="text"
                                        class="input"
                                    >

                                    <span
                                        v-if="volumeSalesErrors['defaultInput.name.value']"
                                        class="form-group__error"
                                    >{{ volumeSalesErrors['defaultInput.name.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-input-group', {'is-error' : volumeSalesErrors['defaultInput.count.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.count.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => field.defaultInput.count.value = target.unmasked"
                                    />

                                    <div class="form-input-group__append">
                                        <select class="select" v-model="field.defaultInput.units.value">
                                            <option
                                                v-for="(val, key) in field.defaultInput.units.values"
                                                :key="key"
                                                :value="key"
                                            >{{ val }}</option>
                                        </select>
                                    </div>
                                </div>

                                <span
                                    v-if="volumeSalesErrors['defaultInput.count.value']"
                                    class="form-group__error"
                                >{{ volumeSalesErrors['defaultInput.count.value'][0] }}</span>
                            </td>

                            <td class="align-top">
                                <div  :class="['form-input-group', {'is-error': volumeSalesErrors['defaultInput.seasonal.value'] }]">
                                    <div class="form-input-group__prepend">
                                        <input
                                            v-model="field.defaultInput.seasonal.isActive"
                                            type="checkbox"
                                            :checked="field.defaultInput.seasonal.isActive"
                                        >
                                    </div>

                                    <DatePicker
                                        v-model:value="field.defaultInput.seasonal.value"
                                        popup-class="mx-datepicker-popup-hide-header"
                                        format="MMM"
                                        type="month"
                                        value-type="M"
                                        input-class="input"
                                        :multiple="true"
                                        :editable="false"
                                        :lang="locale"
                                        placeholder="сезонность"
                                        :disabled="!field.defaultInput.seasonal.isActive"
                                        @clear="field.defaultInput.seasonal.isActive = false"
                                    ></DatePicker>
                                </div>

                                <div
                                    v-if="volumeSalesErrors['defaultInput.seasonal.value']"
                                    class="form-group__error"
                                >{{ volumeSalesErrors['defaultInput.seasonal.value'][0] }}</div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : volumeSalesErrors['defaultInput.price.value'] }]">
                                    <VueNumber
                                        v-model="field.defaultInput.price.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => field.defaultInput.price.value = target.unmasked"
                                    />

                                    <span
                                        v-if="volumeSalesErrors['defaultInput.price.value']"
                                        class="form-group__error"
                                    >{{ volumeSalesErrors['defaultInput.price.value'][0] }}</span>
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
                            <td class="text-right" colspan="5">
                                <b>Общая сумма объема продаж:</b> {{ numberFormat(totalSum) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import dayjs from 'dayjs';
import axios from '@/api/axios';
import projectFormMixin from '@/mixins/projectFormMixin';
import FormHintButton from '@/components/form/FormHintButton';
import DatePicker from '@/plugins/datePicker';

export default {
    name: 'VolumeSalesComponent',
    mixins: [projectFormMixin],
    components: {
        FormHintButton,
        DatePicker
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
            volumeSalesErrors: {},
            activeIndex: null
        }
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
                const itemPrice = parseFloat(item.price);
                return sum + (itemCount * itemPrice);
            }, 0);
        },

        ...mapGetters({ locale: 'lang/locale' }),
    },
    watch: {
        'field.inputs': function (inputs) {
            this.field.value = inputs.length
        },
        'field.defaultInput.seasonal.isActive': function (checked) {
            const seasonalValue = this.field.defaultInput.seasonal.value ?? [];

            this.field.defaultInput.seasonal.value = checked ? seasonalValue : null;
        }
    },
    methods: {
        /**
         * Очищаем или заполняем форму
         */
        clearOrFillInTheField(index) {
            const id = (index !== null) ? this.field.inputs[index].id : null;

            Object.keys(this.field.defaultInput).map(key => {
                const defaultValue = this.field.defaultInput[key]['defaultValue'];

                this.field.defaultInput[key]['value'] = (id !== null) ? this.field.inputs[index][key] : defaultValue;

                if (this.field.defaultInput[key]['hasToggle']) {
                    this.field.defaultInput[key]['isActive'] = Boolean(this.field.defaultInput[key]['value']);
                }
            });
        },

        /**
         * Редактируем элемент
         */
        deleteField(index, e) {
            e.preventDefault();

            const id = this.field.inputs[index]['id'];

            this.setLoading(true);

            axios.put(this.getBaseUrl() + '/volume-sales/' + id)
                .then(() => this.field.inputs.splice(index, 1))
                .finally(() => this.setLoading(false));

            this.clearForm();
        },

        /**
         * Редактируем элемент
         */
        editField(index, e) {
            e.preventDefault();

            this.volumeSalesErrors = {};
            this.activeIndex = index;

            this.clearOrFillInTheField(index);
        },

        /**
         * Очистка заполненной формы
         */
        clearForm() {
            this.activeIndex = null;

            this.clearOrFillInTheField(null);

            this.volumeSalesErrors = {}
        },

        /**
         * Возвращает массив названий месяцев
         *
         * @param  {array} months
         * @return string
         */
        getMonthNames(months = []) {
            return months
                .map(item => dayjs().locale(this.locale).set('month', item - 1).format('MMM'))
                .join(', ');
        },

        /**
         * Сохраняем заполненную форму
         */
        saveField() {
            this.volumeSalesErrors = {};
            this.setLoading(true);

            axios.post(this.getBaseUrl() + '/volume-sales', this.field)
                .then(response => {
                    this.field.inputs = response.data;
                    this.clearForm();
                })
                .catch(error => this.volumeSalesErrors = error.response.data.errors)
                .finally(()=> this.setLoading(false));
        }
    }
}
</script>
