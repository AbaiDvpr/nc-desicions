<template>
    <div class="wrap wrap--bg">
        <div class="form-group">
            <div :class="['form-group__input', errors[name + '.value'] ? 'is-error' : '']">
                <div class="form-group__label">
                    <span>{{ field.title }}</span>
                    <FormHintButton :message="field.hint" />
                </div>

                <span class="form-group__subtitle">{{ field.placeholder }}</span>

                <div class="form-group__input">
                    <select class="select" v-model="field.value">
                        <option
                            v-for="(optionName, optionKey) in field.values"
                            :value="optionKey"
                        >{{ optionName }}</option>
                    </select>
                </div>

                <div v-if="errors[name + '.value']" class="form-group__error">
                    <span>{{ errors[name + '.value'][0] }}</span>
                </div>
            </div>
        </div>

        <template v-if="field['inputs'][field.value]">
            <div v-for="(itemField, itemName) in field['inputs'][field.value]" class="row">
                <div
                    v-for="(subField, subName) in itemField"
                    :class="[subField.column]"
                >
                    <component
                        :is="getNameComponent(subField.type)"
                        :name="name + '.inputs.' + field.value + '.' + itemName + '.' + subName"
                        :errors="errors"
                        :field="subField"
                    ></component>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';
import TextComponent from './TextComponent';
import NumericComponent from './NumericComponent';
import CheckboxComponent from './CheckboxComponent';
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'AttractingInvestmentComponent',
    mixins: [projectFormMixin],
    components: {
        TextComponent,
        NumericComponent,
        FormHintButton,
        CheckboxComponent
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
    }
}
</script>
