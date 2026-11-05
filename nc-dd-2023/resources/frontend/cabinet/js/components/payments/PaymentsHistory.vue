<template>
    <AppLoader v-if="isMainLoading" />

    <AppAlert
        v-else-if="isEmpty"
        type="info"
        :message="message"
    />

    <AppAlert
        v-else-if="isError"
        type="danger"
        :message="$t('whoops')"
    />

    <template v-else>
        <h2 class="title-secondary">{{ $t('paymentHistory') }}</h2>

        <div  class="panel">
            <div class="table-wrap">
                <table class="table table--primary table--layout-fixed" style="min-width: 700px;">
                    <thead>
                    <tr>
                        <th style="width: 80px;"></th>

                        <th style="width: 80px;"></th>

                        <th>Комментарии</th>

                        <th style="width: 180px;">Сумма</th>

                        <th style="width: 180px;">Остаток</th>

                        <th style="width: 180px;">Дата транзакции</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="(history, index) in data" :key="history.id">
                        <td class="text-center">{{ index + 1 }}</td>

                        <td class="text-center">
                            <AppIcon
                                v-if="history.operation === 'refill'"
                                :name="history.operation"
                                modify="icon--success"
                            />

                            <AppIcon
                                v-else
                                :name="history.operation"
                                modify="icon--danger"
                            />
                        </td>

                        <td>{{ history.comment }}</td>

                        <td class="text-right">{{ numberFormat(history.price) + ' ₸' }}</td>

                        <td class="text-right">{{ numberFormat(history.residue) + ' ₸' }}</td>

                        <td class="text-center">{{ history.paidAt }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    <div v-if="isShowLoadBtn" class="btn-group btn-group--center">
        <AppButtonLoader
            :isLoading="isLoading"
            :method="loadMore"
        >{{ $t('loadMore') }}</AppButtonLoader>
    </div>
</template>

<script>
import API from '@/api/payments';

export default {
    name: 'PaymentsHistory',
    data() {
        return {
            data:          [],
            isLoading:     false,
            isMainLoading: false,
            isEmpty:       false,
            isError:       false,
            isShowLoadBtn: false,
            params:        {
                page:  1,
                limit: 10,
            },
            message:       '',
        }
    },
    methods: {
        /**
         * Возвращает список историй
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isMainLoading = true;

            try {
                const data = await API.getHistories();

                this.data = data;
                this.isEmpty = !Boolean(data.length);
                this.isShowLoadBtn = data.length > this.params.limit;

                if (this.isEmpty) {
                    this.message = 'В данный момент история пуста';
                }
            } catch (error) {
                this.isError = true;

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Подгружает историю
         *
         * @return {Promise<void>}
         */
        async loadMore() {
            this.isLoading = true;
            this.params.page += 1;

            try {
                const data = await API.getHistories(this.params);

                this.data = [...this.data, ...data];
                this.isShowLoadBtn = data.length >= this.params.limit;
            } finally {
                this.isLoading = false;
            }
        }
    },
    created() {
        this.initialize();
    }
}
</script>
