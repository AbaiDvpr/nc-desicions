<template>
    <div class="tabs">
        <ul class="tabs-nav">
            <li
                v-for="({ index, name }) in tabs"
                :class="['tabs-nav__item', { 'is-active': activeTab === index }]"
                @click="selectTab(index)"
                :key="name"
                v-html="name"
            />
        </ul>

        <slot></slot>
    </div>
</template>

<script>
export default {
    name: 'AppTabs',
    data() {
        return {
            tabs: [],
            activeTab: 0
        }
    },
    provide() {
        const tabsProvider = { tabs: this.tabs };

        Object.defineProperty(tabsProvider, 'activeTab', {
            enumerable: true,
            get: () => this.activeTab
        });

        return {
            tabsProvider
        }
    },
    methods: {
        selectTab(i) {
            this.activeTab = i;
        }
    },
    mounted() {
        this.selectTab(0);
    }
}
</script>
