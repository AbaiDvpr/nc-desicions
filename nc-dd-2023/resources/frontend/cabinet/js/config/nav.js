export default [
    { name: 'home', to: { name: 'home' }, icon: 'home-2', checkUserEmail: false },
    { name: 'profile', to: { name: 'profile' }, icon: 'user', checkUserEmail: false },
    { name: 'financialModels', to: { name: 'models.home' }, icon: 'lines', checkUserEmail: false },
    { name: 'managementAccounting', to: { name: 'companies.home' }, icon: 'book', checkUserEmail: false },
    { name: 'counterparties', to: { name: 'counterparties' }, icon: 'users', checkUserEmail: false },
    // { name: 'paymentHistory', to: { name: 'payments.home' }, icon: 'calendar', checkUserEmail: false },
    { name: 'tariff', to: { name: 'tariff' }, icon: 'trophy', checkUserEmail: false  },
    { name: 'tariffs', to: { name: 'tariffs' }, icon: 'crown', checkUserEmail: false  },
    { name: 'courses', to: { name: 'courses.home' }, icon: 'cap', checkUserEmail: false },
    { name: 'about', to: { name: 'about' }, icon: 'info', checkUserEmail: false }
]
