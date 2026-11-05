<template>
    <div :class="['rating', { 'is-voted': isVoted }]">
        <div class="rating__stars">
            <button v-for="star in stars" :title="star" @click="update(star)">
                <AppIcon name="star" modify="icon--sm" />
            </button>

            <div class="rating__stars-fill" :style="`width: ${percentage};`">
                <button v-for="star in stars" :title="star" @click="update(star)">
                    <AppIcon name="star" modify="icon--sm icon--star-solid"  />
                </button>
            </div>
        </div>

        <div class="rating__info">
            {{ rating }} <span>({{ votes }})</span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AppRating',
    emits: ['update'],
    props: {
        rating: {
            type: Number,
            required: true,
            default: 0,
        },
        votes: {
            type: Number,
            required: false,
            default: 0,
        },
        isVoted: {
            type: Boolean,
            required: false,
            default: false,
        },
        stars: {
            type: Number,
            required: false,
            default: 5,
        },
    },
    computed: {
        /**
         * Текущий процент рейтинга
         *
         * @returns {String}
         */
        percentage() {
            return this.rating * 100 / this.stars + '%';
        }
    },
    methods: {
        /**
         * Обновляет рейтинг
         *
         * @param {Number} star
         * @return {void}
         */
        update(star) {
            if (this.isVoted) {
                return;
            }

            this.$emit('update', star);
        }
    }
};
</script>
