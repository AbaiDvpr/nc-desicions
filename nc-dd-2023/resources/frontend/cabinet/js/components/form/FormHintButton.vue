<template>
    <span
        v-if="message"
        class="form-group__hint"
        @click="showHint"
    >
        <AppIcon name="question" />
    </span>
</template>

<script>
import notyf from '@/plugins/notyf';

export default {
    name: 'ProjectHintButton',
    props: {
        message: {
            type: String,
            required: false
        }
    },
    methods: {
        showHint() {
            notyf.dismissAll();
            notyf.open({
                type: 'hint',
                message: this.message,
                dismissible: true,
                duration: 60 * 1000,
                icon: false,
                position: {
                    x: 'right',
                    y: 'bottom',
                }
            });
        }
    }
}
</script>
