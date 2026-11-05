<template>
    <router-link
        :to="{name: urlName}"
        class="banner"
    >
        <div class="banner__content">
            <h2 class="banner__title">{{ title }}</h2>

            <p class="banner__slogan">{{ slogan }}</p>
        </div>

        <div class="banner__image">
            <img :src="image" :alt="title">
        </div>
    </router-link>
</template>

<script>
export default {
    name: 'CourseBanner',
    data() {
        return {
            title: 'Бизнес-мастерство начинается здесь!',
            slogan: 'Курс "Бизнес-мастерство: Онлайн-обучение для финансового успеха" – это ваш ключ к миру бизнеса и финансов. Этот курс предлагает серию наглядных онлайн-видеоуроков, охватывающих самые важные аспекты бизнеса – от управления финансами до маркетинговых стратегий и операционного менеджмента.',
            image: '/static/images/cabinet/banners/banner-card-1.svg',
            urlName: 'courses.home'
        }
    },
};
</script>
