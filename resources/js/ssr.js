import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createInertiaApp } from '@inertiajs/vue3'
import createServer from '@inertiajs/vue3/server'

createServer((page) => createInertiaApp({
    page,
    render: renderToString,
    resolve: name => require(`./Pages/${name}`),
    title: title => title ? `${title} - Pro Task` : 'Pro Task',
    setup({ app, props, plugin }) {
        return createSSRApp({
            render: () => h(app, props),
        }).use(plugin)
    },
}))
