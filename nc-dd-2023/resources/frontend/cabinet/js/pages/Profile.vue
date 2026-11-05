<template>
    <h1 class="title-primary">{{ $t($route.meta.title) }}</h1>

    <section class="profile">
        <div class="row">
            <div class="col-12 col-3xl-7 profile__col">
                <h2 class="profile__title">{{ $t('editingPersonalData') }}</h2>

                <div class="profile-card panel">
                    <div class="profile-card__body">
                        <div class="profile-card__left">
                            <div class="profile-avatar">
                                <button
                                    :class="['profile-avatar__upload', { 'is-loading': avatar.isLoading }]"
                                    @click="uploadAvatar"
                                >
                                    <i v-if="avatar.isLoading" class="profile-avatar__upload-loader loader"></i>

                                    <AppIcon v-else name="camera" />
                                </button>

                                <img
                                    class="profile-avatar__image"
                                    :src="avatar.url ?? avatar.default"
                                    :alt="$t('userPersonalData.avatar')"
                                >
                            </div>

                            <p class="profile__hint mb-0">{{ $t('personalDataProtection') }}</p>
                        </div>

                        <div class="profile-card__right">
                            <div :class="['form-group', { 'is-error': errors.name }]">
                                <div class="form-group__input">
                                    <input
                                        class="input"
                                        type="text"
                                        v-model="form.name"
                                        :placeholder="$t('userPersonalData.name')"
                                    >
                                </div>

                                <div
                                    v-if="errors.name"
                                    class="form-group__error"
                                >{{ errors.name[0] }}</div>
                            </div>

                            <div :class="['form-group', { 'is-error': errors.birthday }]">
                                    <DatePicker
                                        class="form-group__input"
                                        v-model:value="form.birthday"
                                        format="DD.MM.YYYY"
                                        value-type="YYYY.MM.DD"
                                        input-class="input"
                                        :placeholder="$t('userPersonalData.birthday')"
                                    ></DatePicker>

                                <div
                                    v-if="errors.birthday"
                                    class="form-group__error"
                                >{{ errors.birthday[0] }}</div>
                            </div>

                            <div :class="['form-group', { 'is-error': errors.mobile }]">
                                <div class="form-group__input">
                                    <input
                                        type="text"
                                        v-model="form.mobile"
                                        v-maska data-maska="+7 (###) ###-##-##"
                                        :placeholder="$t('userPersonalData.phone')"
                                        class="input"
                                    >
                                </div>

                                <div
                                    v-if="errors.mobile"
                                    class="form-group__error"
                                >{{ errors.mobile[0] }}</div>
                            </div>

                            <div :class="['form-group mb-0', { 'is-error': errors.email }]">
                                <div class="form-group__input">
                                    <input
                                        :value="form.email"
                                        type="email"
                                        :placeholder="$t('userPersonalData.email')"
                                        disabled
                                        :title="$t('userPersonalData.emailWarning')"
                                        class="input"
                                    >
                                </div>

                                <div
                                    v-if="errors.email"
                                    class="form-group__error"
                                >{{ errors.email[0] }}</div>
                            </div>

                            <div class="btn-group mt-32">
                                <button
                                    class="btn btn--outline-primary"
                                    @click="resetFormInfo"
                                >{{ $t('buttons.cancel') }}</button>

                                <button
                                    class="btn btn--primary"
                                    @click="onSubmit"
                                >{{ $t('buttons.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-3xl-5 profile__col">
                <h2 class="profile__title">{{ $t('changeAccountPassword') }}</h2>

                <div class="profile-card panel">
                    <div :class="['form-group', { 'is-error': errors.current_password }]">
                        <div class="form-group__input">
                            <input
                                v-model="form.current_password"
                                type="password"
                                :placeholder="$t('userPasswordData.currentPassword')"
                                class="input"
                            >
                        </div>

                        <div
                            v-if="errors.current_password"
                            class="form-group__error"
                        >{{ errors.current_password[0] }}</div>
                        </div>

                    <div :class="['form-group', { 'is-error': errors.password }]">
                        <div class="form-group__input">
                            <input
                                v-model="form.password"
                                type="password"
                                :placeholder="$t('userPasswordData.newPassword')"
                                class="input"
                            >
                        </div>

                        <div
                            v-if="errors.password"
                            class="form-group__error"
                        >{{ errors.password[0] }}</div>
                    </div>

                    <div :class="['form-group mb-0', { 'is-error': errors.password_confirmation }]">
                        <div class="form-group__input">
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                :placeholder="$t('userPasswordData.newPasswordConfirm')"
                                class="input"
                            >
                        </div>

                        <div
                            v-if="errors.password_confirmation"
                            class="form-group__error"
                        >{{ errors.password_confirmation[0] }}</div>
                    </div>

                    <p class="profile__hint mb-0">
                        {{ $t('userPasswordData.passwordWarning') }}
                    </p>

                    <div class="btn-group mt-32">
                        <button
                            class="btn btn--outline-primary"
                            @click="resetFormSecure"
                        >{{ $t('buttons.cancel') }}</button>

                        <button
                            class="btn btn--primary"
                            @click="onSubmit"
                        >{{ $t('buttons.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import { mapGetters } from 'vuex';
import DatePicker from '@/plugins/datePicker';

export default {
    name: 'Profile',
    data() {
        return {
            form: {
                name:                  '',
                email:                 '',
                mobile:                '',
                birthday:              '',
                current_password:      '',
                password:              '',
                password_confirmation: '',
            }
        }
    },
    components: {
        DatePicker
    },
    computed: {
        ...mapGetters({
            user: 'profile/data',
            avatar: 'profile/avatar',
            errors: 'profile/errors',
        })
    },
    methods: {
        /**
         * Обработчик
         *
         * @return {void}
         */
        onSubmit() {
            this.$store.dispatch('profile/update', { ...this.form });
        },

        /**
         * Загружает аватар пользователя
         *
         * @return {void}
         */
        uploadAvatar() {
            this.$store.dispatch('profile/uploadAvatar');
        },

        /**
         * Сбрасывает данные формы
         *
         * @return {void}
         */
        resetFormInfo() {
            this.form.name     = this.user.name;
            this.form.mobile   = this.user.mobile;
            this.form.birthday = this.user.birthday;
        },

        /**
         * Сбрасывает данные формы
         *
         * @return {void}
         */
        resetFormSecure() {
            this.form.current_password      = '';
            this.form.password              = '';
            this.form.password_confirmation = '';
        }
    },
    created() {
        this.form.name     = this.user.name;
        this.form.email    = this.user.email;
        this.form.mobile   = this.user.mobile;
        this.form.birthday = this.user.birthday;
    }
}
</script>
