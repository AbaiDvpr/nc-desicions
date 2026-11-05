<template>
    <div class="wrap">
        <div v-if="field.inputs" class="form-group">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div class="row">
                <div v-for="(input, key) in field.inputs" class="col-6">
                    <div :class="['form-group', errors[name + '.values.' + key] ? 'is-error' : '']">
                        <DatePicker
                            class="form-group__input"
                            v-model:value="field.values[key]"
                            format="YYYY/MM"
                            value-type="YYYY-MM-DD"
                            type="month"
                            input-class="input"
                            :placeholder="input"
                            :disabled="isDisabled(key)"
                            :disabled-date="disabledDate(key)"
                        ></DatePicker>

                        <div class="form-group__error" v-if="errors[name + '.values.' + key]">
                            <span>{{ errors[name + '.values.' + key][0] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import dayjs from 'dayjs';
import DatePicker from '@/plugins/datePicker';
import projectFormMixin from '@/mixins/projectFormMixin';
import FormHintButton from '@/components/form/FormHintButton';

/**
 * @type {dayjs.Dayjs}
 */
const CURRENT_DATE = dayjs();

export default {
    name: 'CalendarRangeComponent',
    mixins: [projectFormMixin],
    components: {
        DatePicker,
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
         * Опции дат
         *
         * @return {Object|undefined}
         */
        options() {
            return this.field.options;
        }
    },
    watch: {
        'field.values.from': function(value) {
            if (value) this.setValueTo();
        }
    },
    methods: {
        /**
         * Устанавливает значения для поля "to"
         *
         * @return {void}
         */
        setValueTo() {
            if (this.options && this.options.to) {
                this.field.values.to = dayjs(this.field.values.from)
                    .add(this.options.to.addYear, 'year')
                    .format('YYYY-MM-DD');
            }
        },

        /**
         * Отключает поле "to", если не указано "from"
         *
         * @param  {string} key
         * @return {boolean}
         */
        isDisabled(key) {
            if (key === 'to') {
                return !Boolean(this.field.values.from);
            }

            return false;
        },

        /**
         * Обработчик для отключения дат
         *
         * @param key
         * @return {(function(*): (*|boolean))|*|(function(*): boolean)}
         */
        disabledDate(key) {
            if (key === 'from') {
                return this.disabledDateFrom;
            }

            return this.disabledDateTo;
        },

        /**
         * Обработчик для отключения дат поля "from"
         *
         * @param  date
         * @return {boolean}
         */
        disabledDateFrom(date) {
            if (this.options && this.options.from) {
                const { subtractYear } = this.options.from;

                return date.valueOf() < CURRENT_DATE
                    .subtract(1, 'month')
                    .subtract(subtractYear, 'year')
                    .valueOf();
            }

            return false;
        },

        /**
         * Обработчик для отключения дат поля "to"
         *
         * @param  date
         * @return {boolean}
         */
        disabledDateTo(date) {
            if (this.options && this.options.to) {
                const { addYear, maxYear } = this.options.to;
                const from = dayjs(this.field.values.from);
                const max = dayjs(this.field.values.from);

                return (date.valueOf() < from.add(addYear, 'year').valueOf()) ||
                       (date.valueOf() > max.add(maxYear, 'year').valueOf());
            }

            return false;
        },

        /**
         * Обновляет дату "to", если она меньше допустимой даты
         *
         * @return {void}
         */
        updateValueTo() {
            const from = this.field.values.from;
            const to   = this.field.values.to;

            if (to && this.options && this.options.to) {
                const { addYear } = this.options.to;

                if (!addYear) return;

                const current = dayjs(to).valueOf();
                const min     = dayjs(from).add(addYear, 'year').valueOf();

                if (current < min) {
                    this.setValueTo();
                }
            }
        }
    },
    created() {
        this.updateValueTo();
    }
}
</script>
