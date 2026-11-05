<template>
    <div class="wrap">
        <div class="form-group">
            <div
                v-if="field.placeholder"
                class="form-group__subtitle"
                v-html="field.placeholder"
            ></div>

            <div v-if="errors['variableCosts.variableCostsOther.inputs']" class="form-group__error">
                {{ errors['variableCosts.variableCostsOther.inputs'][0] }}
            </div>

            <div v-if="field.inputs" class="table-wrap">
                <table class="table table--primary">
                    <thead>
                        <tr>
                            <th v-if="field.withInvest" style="width: 98px;">На инвест период</th>

                            <th>Наименование затрат</th>

                            <th>Затраты за месяц</th>

                            <th>Период</th>

                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(input, index) in field.inputs">
                            <td v-if="field.withInvest" style="width: 98px;">
                                <div :class="['form-group', {'is-error' : errors[defName + '.inputs.'+index+'.invest'] }]">
                                    <input
                                        v-model="input.invest"
                                        type="checkbox"
                                        :checked="input.invest"
                                        class="input"
                                    >

                                    <span
                                        v-if="errors[defName + '.inputs.'+index+'.invest']"
                                        class="form-group__error"
                                    >{{ errors[defName + '.inputs.'+index+'.invest'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : errors[defName + '.inputs.'+index+'.title'] }]">
                                    <input
                                        v-model="input.title"
                                        type="text"
                                        class="input"
                                    >

                                    <span
                                        v-if="errors[defName + '.inputs.'+index+'.title']"
                                        class="form-group__error"
                                    >{{ errors[defName + '.inputs.'+index+'.title'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : errors[defName + '.inputs.'+index+'.value'] }]">
                                    <VueNumber
                                        v-model="input.value"
                                        v-bind="numberConfig"
                                        @input="({ target }) => input.value = target.unmasked"
                                    />

                                    <span
                                        v-if="errors[defName + '.inputs.'+index+'.value']"
                                        class="form-group__error"
                                    >{{ errors[defName + '.inputs.'+index+'.value'][0] }}</span>
                                </div>
                            </td>

                            <td class="align-top">
                                <div :class="['form-group', {'is-error' : errors[defName + '.inputs.' + index + '.period'] }]">
                                    <select
                                        v-model.number="input.period"
                                        class="select"
                                    >
                                        <option v-for="(value, key) in field.periods" :value="key">
                                            {{ value }}
                                        </option>
                                    </select>
                                </div>
                            </td>

                            <td style="width: 52px;">
                                <div class="btn-group btn-group--center">
                                    <button
                                        class="btn btn--danger btn--size-sm"
                                        type="button"
                                        title="Удалить"
                                        @click="deleteField(index, $event)"
                                    >
                                        <AppIcon name="trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
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
                                    <input class="input" v-model="field.defaultInput.title" type="text">
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
                                        <option v-for="(value, key) in field.periods" :value="key">
                                            {{ value }}
                                        </option>
                                    </select>
                                </div>
                            </td>

                            <td style="width: 52px;">
                                <div class="btn-group btn-group--center">
                                    <button
                                        class="btn btn--primary btn--size-sm"
                                        type="button"
                                        title="Сохранить"
                                        @click="saveField"
                                    >
                                        <AppIcon name="save" />
                                    </button>
                                </div>
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
    name: 'VariableCostsOtherComponent',
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
        defName() {
            return 'variableCosts.variableCostsOther';
        }
    },
    watch: {
        'field.inputs': function (inputs) {
            this.field.value = inputs.length
        }
    },
    methods: {
        /**
         * Редактируем элемент
         */
        deleteField(index, e) {
            e.preventDefault();

            this.field.inputs.splice(index, 1);
        },

        /**
         * Сохраняем заполненную форму
         */
        saveField() {
            if (this.field.defaultInput.title && this.field.defaultInput.value) {
                this.field.inputs.push({...this.field.defaultInput});

                this.field.defaultInput.title  = null;
                this.field.defaultInput.value = null;
            }
        }
    }
}
</script>
