<template>
    <div :class="['form-group', errors[name + '.value'] ? 'is-error' : '']">
        <label class="checkbox">
            <input
                class="checkbox__input"
                type="checkbox"
                v-model="field.value"
            />

            <span class="checkbox__label">{{ field.title }}</span>
        </label>

        <div
            v-if="errors[name + '.value']"
            class="form-group__error"
        >{{ errors[name + '.value'][0] }}</div>
    </div>
</template>

<script>
import projectFormMixin from '@/mixins/projectFormMixin';

export default {
    name: 'CheckboxComponent',
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
    }
}
</script>
