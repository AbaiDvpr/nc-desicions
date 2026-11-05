<template>
    <AppButtonLoader
        modify="btn--size-md"
        :method="initialize"
        :isLoading="isMainLoading"
    >
        <template #icon>
            <AppIcon name="import" />
        </template>
    </AppButtonLoader>

    <AppModal
        name="modal-company-import"
        :isLoading="isLoading"
        @close="hideModal"
    >
        <template #header>
            <h2 class="modal__title">Импорт файла</h2>
        </template>

        <template #content>
            <div class="formatted fw-600">
                Пример импортируемого файла можете скачать по
                <a :href="`/static/company/import/opu-dds-balance-example.xlsx`" download>ссылке</a>
            </div>

            <AppAlert
                v-if="inProgress"
                type="info"
                modify="mt-16"
                message="Ваш файл в процессе обработки, пожалуйста подождите"
            />

            <AppAlert
                v-if="isError"
                type="danger"
                modify="mt-16"
                :message="$t('whoops')"
            />
        </template>

        <template #footer>
            <div class="btn-group btn-group--end">
                <AppButtonLoader
                    v-if="!inProgress"
                    :isLoading="isLoading"
                    modify="btn--w-150"
                    :method="onSubmit"
                >Загрузить</AppButtonLoader>
            </div>
        </template>
    </AppModal>
</template>

<script>
import API from '@/api/company';
import notyf from '@/plugins/notyf';

export default {
    name: 'CompanyImportFile',
    props: {
        companyUuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            isMainLoading: false,
            isLoading:     false,
            isError:       false,
            inProgress:    false,
        }
    },
    methods: {
        /**
         * Инициализация модального окна и проверка статуса
         *
         * @return {Promise<void>}
         */
        async initialize() {
            this.isMainLoading = true;
            this.isError       = false;
            this.inProgress    = false;

            try {
                const { status } = await API.getImportFileStatus({
                    companyUuid: this.companyUuid
                });

                this.inProgress = !Boolean(status);
                this.showModal('modal-company-import');
            } catch (error) {
                notyf.error(this.$i18n.t('whoops'));

                throw error;
            } finally {
                this.isMainLoading = false;
            }
        },

        /**
         * Загружает файл
         *
         * @return {void}
         */
        onSubmit() {
            const input = document.createElement('input');

            input.type   = 'file';
            input.accept = '.xlsx, .xls';

            input.addEventListener('change', async () => {
                const [file] = input.files;

                if (file) {
                    const formData = new FormData();

                    formData.append('file', file);
                    formData.append('companyUuid', this.companyUuid);

                    this.isLoading = true;

                    try {
                        const { id } = await API.uploadImportFile(formData);

                        if (id) {
                            this.inProgress = true;
                            this.isError    = false;
                        }
                    } catch (error) {
                        this.isError = true;

                        throw error;
                    } finally {
                        input.remove();
                        this.isLoading = false;
                        this.hideModal();
                    }
                }
            });

            input.hidden = true;
            document.body.append(input);

            input.click();
        }
    }
}
</script>
