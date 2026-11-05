import { createRouter, createWebHashHistory } from 'vue-router';
import i18n from '@/plugins/i18n';
import {checkUserInAllowedUsers, setPageTitle} from '@/helpers/helpers';
import store from  '@/store/store';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import(/* webpackChunkName: "Home" */ '@/pages/Home.vue'),
        meta: {
            title: 'home'
        }
    },
    {
        path: '/profile',
        name: 'profile',
        component: () => import(/* webpackChunkName: "Profile" */ '@/pages/Profile.vue'),
        meta: {
            title: 'profile'
        }
    },
    {
        path: '/models',
        component: () => import(/* webpackChunkName: "ProjectLayout" */ '@/pages/project/BaseLayout.vue'),
        meta: {
            title: 'financialModels'
        },
        children: [
            {
                path: '',
                name: 'models.home',
                component: () => import(/* webpackChunkName: "ProjectHome" */ '@/pages/project/Home.vue'),
                meta: {
                    title: 'financialModels'
                }
            },
            {
                path: 'p/:uuid',
                name: 'models.show',
                component: () => import(/* webpackChunkName: "ProjectShow" */ '@/pages/project/Show.vue'),
                meta: {
                    title: 'financialModels'
                }
            }
        ]
    },
    {
        path: '/companies',
        component: () => import(/* webpackChunkName: "CompanyLayout" */ '@/pages/company/BaseLayout.vue'),
        meta: {
            title: 'managementAccounting',
            checkUserEmail: false
        },
        children: [
            {
                path: '',
                name: 'companies.home',
                component: () => import(/* webpackChunkName: "CompanyHome" */ '@/pages/company/Home.vue'),
                meta: {
                    title: 'managementAccounting'
                },
            },
            {
                path: ':uuid',
                component: () => import(/* webpackChunkName: "CompanyPageLayout" */ '@/pages/company/PageLayout.vue'),
                meta: {
                    title: 'managementAccounting'
                },
                children: [
                    {
                        path: '',
                        name: 'companies.show',
                        component: () => import(/* webpackChunkName: "CompanyShow" */ '@/pages/company/Show.vue'),
                        meta: {
                            title: 'managementAccounting'
                        },
                    },
                    {
                        path: 'opu',
                        name: 'companies.page.opu',
                        component: () => import(/* webpackChunkName: "CompanyPageOpu" */ '@/pages/company/pages/Opu.vue'),
                        meta: {
                            title: 'managementAccounting'
                        },
                    },
                    {
                        path: 'dds',
                        name: 'companies.page.dds',
                        component: () => import(/* webpackChunkName: "CompanyPageDds" */ '@/pages/company/pages/Dds.vue'),
                        meta: {
                            title: 'managementAccounting'
                        },
                    },
                    {
                        path: 'balance',
                        name: 'companies.page.balance',
                        component: () => import(/* webpackChunkName: "CompanyPageBalance" */ '@/pages/company/pages/Balance.vue'),
                        meta: {
                            title: 'managementAccounting'
                        },
                    },
                    {
                        path: 'accounting',
                        name: 'companies.page.accounting',
                        component: () => import(/* webpackChunkName: "CompanyPageAccounting" */ '@/pages/company/pages/Accounting.vue'),
                        meta: {
                            title: 'dailyAccounting'
                        },
                    },
                    {
                        path: 'calendar',
                        name: 'companies.page.calendar',
                        component: () => import(/* webpackChunkName: "CompanyPageCalendar" */ '@/pages/company/pages/Calendar.vue'),
                        meta: {
                            title: 'calendar'
                        },
                    },
                    {
                        path: 'profile',
                        name: 'companies.page.profile',
                        component: () => import(/* webpackChunkName: "CompanyPageProfile" */ '@/pages/company/pages/Profile.vue'),
                        meta: {
                            title: 'companyProfile'
                        },
                    }
                ]
            }
        ]
    },
    {
        path: '/payments',
        component: () => import(/* webpackChunkName: "PaymentsLayout" */ '@/pages/payments/BaseLayout.vue'),
        meta: {
            title: 'payments',
            checkUserEmail: false
        },
        children: [
            {
                path: '',
                name: 'payments.home',
                component: () => import(/* webpackChunkName: "PaymentsHome" */ '@/pages/payments/Home.vue'),
                meta: {
                    title: 'paymentHistory'
                }
            },
            {
                path: 'confirmation/:id',
                name: 'payments.confirmation',
                component: () => import(/* webpackChunkName: "PaymentsConfirmation" */ '@/pages/payments/Confirmation.vue'),
                meta: {
                    title: 'paymentsConfirmation'
                }
            }
        ]
    },
    {
        path: '/about',
        name: 'about',
        component: () => import(/* webpackChunkName: "About" */ '@/pages/About.vue'),
        meta: {
            title: 'about'
        }
    },
    {
        path: '/counterparties',
        name: 'counterparties',
        component: () => import(/* webpackChunkName: "Counterparties" */ '@/pages/Counterparties.vue'),
        meta: {
            title: 'counterparties'
        }
    },
    {
        path: '/courses',
        component: () => import(/* webpackChunkName: "CourseLayout" */ '@/pages/courses/BaseLayout.vue'),
        meta: {
            title: 'courses'
        },
        children: [
            {
                path: '',
                name: 'courses.home',
                component: () => import(/* webpackChunkName: "CourseHome" */ '@/pages/courses/Home.vue'),
                meta: {
                    title: 'courses'
                }
            },
            {
                path: 'modules/:id',
                name: 'courses.show',
                component: () => import(/* webpackChunkName: "CourseShow" */ '@/pages/courses/Show.vue'),
                meta: {
                    title: 'courseContent'
                }
            }
        ]
    },
    {
        path: '/tariffs',
        name: 'tariffs',
        component: () => import(/* webpackChunkName: "Tariffs" */ '@/pages/Tariffs.vue'),
        meta: {
            title: 'tariffs',
            checkUserEmail: false
        }
    },
    {
        path: '/tariff',
        name: 'tariff',
        component: () => import(/* webpackChunkName: "Tariff" */ '@/pages/Tariff.vue'),
        meta: {
            title: 'tariff',
            checkUserEmail: false
        }
    },
    {
        path: '/404',
        name: 'NotFound',
        component: () => import(/* webpackChunkName: "NotFound" */ '@/pages/NotFound.vue'),
        meta: {
            title: '404'
        }
    },
    {
        path: '/:pathMatch(.*)',
        redirect: { name: 'NotFound' }
    }
];
const { locale } = window.data;
const base = `/${locale}/cabinet/`;
const router = createRouter({
    history: createWebHashHistory(base),
    scrollBehavior (to, from, savedPosition) {
        return { x: 0, y: 0 }
    },
    routes,
});

// Guards
router.beforeEach((to, from, next) => {
    store.dispatch('progressBar/start');

    const courseAccess = store.getters['profile/courseAccess'];

    if (
        (to.meta.checkUserEmail && !checkUserInAllowedUsers()) ||
        (to.name.startsWith('courses.') && !courseAccess)
    ) {
        next({ name: 'NotFound' });
    } else {
        setPageTitle(i18n.global.t(to.meta.title));

        store.dispatch('sidebar/show', false);

        next();
    }
});

router.afterEach(() => {
    store.dispatch('progressBar/stop');
});

export default router;
