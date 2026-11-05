<template>
    <div class="company-nav">
        <router-link
            v-for="({ icon, name, description, to, isDisabled, type }, key) in sections"
            :to="to"
            :class="['company-nav-item panel', isDisabled ? 'is-disabled' : '', `company-nav-item--${type}`]"
            :key="key"
        >
            <div class="company-nav-item__icon-wrap">
                <AppIcon :name="icon" />
            </div>

            <h3 class="company-nav-item__title">
                {{ name }}
            </h3>

            <p
                v-if="description"
                class="company-nav-item__subtitle"
            >{{ description }}</p>
        </router-link>
    </div>
</template>

<script>
export default {
    name: 'CompanyNav',
    data() {
        return {
            sections: [
                {
                    icon:        'chart',
                    name:        'Отчет о прибыли и убытках',
                    description: null,
                    to:          {name: 'companies.page.opu'},
                    isDisabled:  false,
                    type:        'primary'
                },
                {
                    icon:        'money-change',
                    name:        'Движение денежных средств',
                    description: null,
                    to:          {name: 'companies.page.dds'},
                    isDisabled:  false,
                    type:        'info'
                },
                {
                    icon:        'wallet',
                    name:        'Статистика и баланс',
                    description: null,
                    to:          {name: 'companies.page.balance'},
                    isDisabled:  false,
                    type:        'danger'
                },
                {
                    icon:        'list',
                    name:        'Ежедневный учёт',
                    description: null,
                    to:          {name: 'companies.page.accounting'},
                    isDisabled:  false,
                    type:        'success'
                },
                {
                    icon:        'calendar',
                    name:        'Календарь',
                    description: null,
                    to:          {name: 'companies.page.calendar'},
                    isDisabled:  false,
                    type:        'primary'
                }
            ]
        }
    }
}
</script>
