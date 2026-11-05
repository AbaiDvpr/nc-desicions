<template>
    <div class="wrap">
        <div :class="['form-group', errors[name + '.value'] ? 'is-error' : '']">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <DatePicker
                class="form-group__input"
                v-model:value="field.value"
                format="YYYY/MM"
                value-type="YYYY-MM-DD"
                type="month"
                input-class="input"
                :placeholder="field.placeholder"
                :disabled-date="disabledDate"
            ></DatePicker>

            <div
                v-if="field.subTitle"
                class="form-group__subtitle form-group__subtitle--mt mb-0"
            >{{ field.subTitle }}</div>

            <div v-if="errors[name + '.value']" class="form-group__error">
                <span>{{ errors[name + '.value'][0] }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import DatePicker from '@/plugins/datePicker';
import projectFormMixin from '@/mixins/projectFormMixin';
import FormHintButton from '@/components/form/FormHintButton';
import dayjs from 'dayjs';

export default {
    name: 'CalendarComponent',
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
        },

        /**
         * Диапазон дат
         *
         * @return {boolean}
         */
        hasRange() {
            return Boolean(this.options?.from && this.options?.to);
        }
    },
    methods: {
        /**
         * Обработчик для отключения дат
         *
         * @param  date
         * @return {boolean}
         */
        disabledDate(date) {
            if (this.hasRange) {
                const from = dayjs(this.field.options.from);
                const to   = dayjs(this.field.options.to);

                return (date.valueOf() < from.valueOf()) || (date.valueOf() > to.valueOf());
            }

            return false;
        },

        /**
         * Обновляет дату, если она выходит за пределы диапазона
         *
         * @return {void}
         */
        updateValueByRange() {
            if (!this.hasRange) return;

            const from = dayjs(this.options.from);
            const to   = dayjs(this.options.to);

            if (this.field.value) {
                const current = dayjs(this.field.value);

                if (current.valueOf() < from.valueOf() || current.valueOf() > to.valueOf()) {
                    this.field.value = from.format('YYYY-MM-DD');
                }
            } else {
                this.field.value = from.format('YYYY-MM-DD');
            }
        }
    },
    created() {
        this.updateValueByRange();
    }
}
</script>
