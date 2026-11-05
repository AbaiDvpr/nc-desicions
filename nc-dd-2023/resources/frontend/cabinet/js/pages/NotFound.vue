<template>
    <section class="not-found">
        <div class="not-found__code">{{ $route.meta.title }}</div>

        <h1 class="not-found__title">{{ $t('pageNotFound') }}</h1>

        <p class="not-found__subtitle">{{ $t('pageNotFoundDesc') }}</p>

        <router-link
            class="btn btn--primary mt-16"
            :to="{ name: 'home' }"
        >{{ $t('goToMain') }}</router-link>
    </section>
</template>

<script>
export default {
    name: 'NotFound'
}
</script>
