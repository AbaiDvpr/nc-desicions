<template>
    <div class="wrap">
        <div :class="['form-group', errors[name + '.value'] ? 'is-error' : '']">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div
                v-if="field.placeholder"
                class="form-group__subtitle"
            >{{ field.placeholder }}</div>
        </div>

        <template v-if="field['inputs']">
            <div class="row">
                <div
                    v-for="(subField, subName) in field['inputs']"
                    :class="[subField.column]"
                >
                    <component
                        :is="getNameComponent(subField.type)"
                        :name="name + '.inputs.' + subName"
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
    name: 'PlannedCapacityComponent',
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
