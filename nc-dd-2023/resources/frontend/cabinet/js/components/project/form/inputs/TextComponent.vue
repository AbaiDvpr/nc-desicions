<template>
    <div class="wrap">
        <div :class="['form-group', errors[name + '.value'] ? 'is-error' : '']">
            <div class="form-group__label">
                <span>{{ field.title }}</span>
                <FormHintButton :message="field.hint" />
            </div>

            <div
                v-if="field.subTitle"
                class="form-group__subtitle"
            >{{ field.subTitle }}</div>

            <div class="form-group__input">
                <VueNumber
                    v-if="field.isValuePercent"
                    v-model="field.value"
                    v-bind="percentConfig"
                    :placeholder="field.placeholder"
                    @input="({ target }) => field.value = target.unmasked"
                />

                <VueNumber
                    v-else-if="field.isValueMonth"
                    v-model="field.value"
                    v-bind="{...numberConfig, max: field.maxValue ? field.maxValue : 50}"
                    :placeholder="field.placeholder"
                    @input="({ target }) => field.value = target.unmasked"
                />

                <input
                    v-else
                    type="text"
                    v-model="field.value"
                    :placeholder="field.placeholder"
                    class="input"
                >
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
    name: 'TextComponent',
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
        errors: {
            type: Object
        }
    }
}
</script>
