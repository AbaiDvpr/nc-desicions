<template>
    <div v-show="isActive" class="tabs-content panel">
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: 'AppTab',
    inject: ['tabsProvider'],
    props: {
        name: {
            type: String,
            default: 'Tab'
        }
    },
    data() {
        return {
            index: 0
        }
    },
    computed: {
        isActive() {
            return this.tabsProvider.activeTab === this.index;
        }
    },
    beforeMount() {
        const index = this.tabsProvider.tabs.length;
        const name  = this.name;

        this.index = index;
        this.tabsProvider.tabs.push({ index, name });
    }
}
</script>
