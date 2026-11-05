<template>
    <div class="wrap">
        <div :class="['form-group', errors[name + '.value'] ? 'is-error' : '']">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div v-if="field.subTitle" class="form-group__subtitle">{{ field.subTitle }}</div>

            <div class="form-group__input">
                <VueNumber
                    v-model="field.value"
                    v-bind="numberConfig"
                    :placeholder="field.placeholder"
                    :disabled="isDisabled"
                    @input="({ target }) => field.value = target.unmasked"
                />
            </div>

            <div v-if="errors[name + '.value']" class="form-group__error">
                <span>{{ errors[name + '.value'][0] }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'NumericComponent',
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
        parentField: {
            type: Object
        },
        errors: {
            type: Object
        }
    },
    computed: {
        isDisabled() {
            return Boolean(this.field.disabled);
        }
    },
    created() {
        this.field.value = this.field.value ?? '';
    }
}
</script>
