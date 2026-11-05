<template>
    <div class="wrap">
        <div :class="['form-group', errors[name + '.value'] ? 'is-error' : '']">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div class="form-group__input">
                <select class="select" v-model="field.value">
                    <option
                        v-if="field.placeholder"
                        value="null"
                    >{{ field.placeholder }}</option>
                    <option
                        v-for="(optionName, optionKey) in field.values"
                        :value="optionKey"
                    >{{ optionName }}</option>
                </select>
            </div>

            <div
                v-if="errors[name + '.value']"
                class="form-group__error"
            >
                <span>{{ errors[name + '.value'][0] }}</span>
            </div>
        </div>

        <template v-if="Number(field.value) === 1">
            <div v-if="field['inputs'][field.value]" class="row">
                <div
                    v-for="(subField, subName) in field['inputs'][field.value]"
                    :class="[subField.column]"
                >
                    <component
                        :is="getNameComponent(subField.type)"
                        :name="name + '.inputs.' + field.value + '.' + subName"
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
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'TurnoverComponent',
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
}
</script>
