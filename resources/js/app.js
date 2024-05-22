import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import VueApexCharts from "vue3-apexcharts"

const clickOutside = {
    beforeMount: (el, binding) => {
        el.clickOutsideEvent = event => {
            if (!(el == event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.addEventListener("click", el.clickOutsideEvent);
    },
    unmounted: el => {
        document.removeEventListener("click", el.clickOutsideEvent);
    },
};

createInertiaApp({
    progress: {
        delay: 100,
        color: '#7366ff',
        includeCSS: true,
        showSpinner: true,
    },
    resolve: name => require(`./Pages/${name}`),
    title: title => title ? `${title}`:`Pro Task`,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .mixin({ methods: { route : route } })
            .mixin(require('./base'))
            .use(plugin)
            .use(VueApexCharts)
            .directive("click-outside", clickOutside)
            .mount(el)
    },
})
