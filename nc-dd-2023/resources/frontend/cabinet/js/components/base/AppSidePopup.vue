<template>
    <Teleport to="body">
        <Transition name="modal" appear>
            <div
                v-if="isShow"
                :class="['side-popup', {'is-compact': isCompact}]"
                @keyup.esc="close"
                v-click-out-side="close"
            >
                <div class="side-popup__body">
                    <button
                        v-if="isShowClose"
                        :class="['side-popup__close', { 'is-disabled': isLoading }]"
                        @click="close"
                    >&times;</button>

                    <div v-if="$slots.header" class="side-popup__header">
                        <slot name="header"></slot>
                    </div>

                    <div class="side-popup__main">
                        <div v-if="$slots.content" class="side-popup__content">
                            <slot name="content"></slot>
                        </div>

                        <div v-if="$slots.footer" class="side-popup__footer">
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
import clickOutSide from '@/directives/clickOutSide';

export default {
    name: 'AppSidePopup',
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
        }
    },
    directives: { clickOutSide },
    computed: {
        ...mapState({
            currentSidePopup: state => state.sidePopup.current,
            isCompact: state => state.sidebar.isCompact
        }),

        isShow() {
            return this.currentSidePopup === this.name;
        }
    },
    methods: {
        close() {
            if (!this.isLoading) {
                this.$emit('close');
            }
        }
    }
}
</script>
