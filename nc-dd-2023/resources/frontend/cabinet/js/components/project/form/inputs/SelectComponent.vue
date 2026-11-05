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
                        value="0"
                    >{{ field.placeholder }}</option>
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
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';
import FormHintButton from '@/components/form/FormHintButton';

export default {
    name: 'SelectComponent',
    mixins: [projectFormMixin],
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
    components: {
        FormHintButton
    }
}
</script>
