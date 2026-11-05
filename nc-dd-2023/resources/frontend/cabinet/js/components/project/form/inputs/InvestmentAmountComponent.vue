<template>
    <div class="wrap wrap--bg">
        <div class="form-group">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div class="form-group__subtitle">{{ field.placeholder }}</div>

            <div v-if="field.inputs" class="row">
                <div
                    v-for="(subField, subName) in field.inputs"
                    :class="[subField.column]"
                >
                    <component
                        :is="getNameComponent(subField.type)"
                        :name="name + '.inputs.' + subName"
                        :errors="errors"
                        :field="subField"
                        :parentField="field"
                    ></component>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';
import NumericComponent from './NumericComponent';
import TextComponent from './TextComponent';
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'InvestmentAmountComponent',
    mixins: [projectFormMixin],
    components: {
        NumericComponent,
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
    }
}
</script>
