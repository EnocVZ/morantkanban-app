<template>
    <img v-if="name === 'white'" class="logo w-[180px] h-auto" src="/images/logo_white.png" alt="Logo">
    <img v-else class="logo w-[180px] h-auto" src="/images/logo.png" alt="Logo" />
</template>
<script>
export default {
    name: 'logo',
    props: {
        name: String,
    },
}
</script>
