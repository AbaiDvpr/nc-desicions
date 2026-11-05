<template>
    <Teleport to="body">
        <Transition name="modal" appear>
            <div v-if="isShow" :class="{ 'modal': true, [`modal--${type}`]: type }" @keyup.esc="close">
                <div class="modal__body">
                    <button
                        v-if="isShowClose"
                        :class="['modal__close', { 'is-disabled': isLoading }]"
                        @click="close"
                    >&times;</button>

                    <div v-if="$slots.header" class="modal__header">
                        <slot name="header"></slot>
                    </div>

                    <div class="modal__main">
                        <div v-if="$slots.content" class="modal__content">
                            <slot name="content"></slot>
                        </div>

                        <div v-if="$slots.footer" class="modal__footer">
                            <slot name="footer"></slot>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import { mapState } from 'vuex';

export default {
    name: 'AppModal',
    emits: ['close'],
    props: {
        name: {
            type: String,
            required: true
        },
        isShowClose: {
            type: Boolean,
            required: false,
            default: true,
        },
        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
        type: {
            type: String,
            default: ''
        }
    },
    computed: {
        ...mapState({
            currentModal: state => state.modal.current
        }),

        isShow() {
            return this.currentModal === this.name;
        }
    },
    methods: {
        close() {
            if (!this.isLoading && this.isShowClose) {
                this.$emit('close');
            }
        }
    }
}
</script>
