<template>
    <div class="course-player">
        <iframe
            class="course-player__frame"
            :src="`https://www.youtube.com/embed/${id}?autoplay=${Number(autoplay)}&playsinline=1&rel=0`"
            title="NC video player"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
        ></iframe>
    </div>
</template>

<script>
export default {
    name: 'CourseLessonPlayer',
    props: {
        id: {
            type: String,
            required: true
        },
        autoplay: {
            type: Boolean,
            required: false,
            default: false
        },
    }
}
</script>
