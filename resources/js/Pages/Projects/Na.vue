<template>
    <div class="h-full no__workspace" :style="{backgroundImage: 'url(/images/bg/color_dark_red.svg)'}">
        <Head :title="__(title)" />
        <div class="welcome">
            <div class="message">
                <h1>No se encontro el espacio de trabajo!</h1>
                <button v-if="$page.props.auth.user.role.slug === 'admin'" @click="create_workspace=!create_workspace" class="create_new">Crear nuevo</button>
                <p v-if="$page.props.auth.user.role.slug !== 'admin'">Aún no te has unido a ningún espacio de trabajo. Póngase en contacto con el administrador.</p>
            </div>
        </div>
        <create-workspace v-if="create_workspace" @create-workspace="create_workspace = false" />
    </div>
</template>

<script>
import {Head, Link} from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Pagination from '@/Shared/Pagination'
import mapValues from 'lodash/mapValues'
import throttle from 'lodash/throttle'
import BoardViewMenu from '@/Shared/BoardViewMenu'
import moment from 'moment'
import SearchInput from '@/Shared/SearchInput'
import BoardFilter from "@/Shared/BoardFilter";
import CreateWorkspace from "@/Shared/Modals/CreateWorkspace";


export default {
    components: {
        CreateWorkspace,
        BoardFilter,
        Head,
        Icon,
        Link,
        BoardViewMenu,
        Pagination,
        SearchInput,
    },
    layout: Layout,
    props: {
        title: String,
        auth: Object,
        project: Object,
        workspace: Object,
        time_logs: Object,
        total_duration: { required: false },
        filters: Object,
    },
    data() {
        return {
            open_filter: false,
            create_workspace: false,
        }
    },
    computed: {

    },
    created() {
        this.moment = moment
    },
    methods: {
    },
}
</script>
