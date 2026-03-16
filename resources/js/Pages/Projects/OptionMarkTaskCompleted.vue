<template>
    <transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 z-10">
                <!-- Header -->
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ __('Automatizar tareas completadas') }}
                        </h2>

                        <p class="text-sm text-gray-500 mt-1">
                            Las tareas se marcarán como completadas automáticamente.
                        </p>
                    </div>

                    <button @click="close" class="text-gray-400 hover:text-gray-600 transition">
                        ✕
                    </button>
                </div>
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">

                    <div>
                        <p class="text-sm font-medium text-gray-700">
                            {{ __('Marcar como completadas') }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Las tareas se marcarán como completadas automáticamente
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">

                        <input type="checkbox" class="sr-only peer" v-model="enableDone" @change="updateSublistInfo"
                            />

                        <div class="w-11 h-6 rounded-full transition flex items-center
  justify-center
  peer-checked:bg-green-500
  bg-gray-300">

                            <!-- Loader -->
                            <svg v-if="loadingToggle" class="animate-spin h-3 w-3 text-white" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4l3-3-3-3v4a10 10 0 100 20" />
                            </svg>

                        </div>

                        <div v-if="!loadingToggle"
                            class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition peer-checked:translate-x-5">
                        </div>

                    </label>


                </div>

            </div>

        </div>
    </transition>

</template>
<script>
import axios from 'axios'
import { get } from 'lodash';

export default {
    props: {
        showModal: {
            type: Boolean,
            required: true,
        },
        sublist: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            enableDone: false,
            loadingToggle: false,
        }
    },
    watch:{
        sublist(newVal) {
            if (this.showModal) {
                this.enableDone = newVal.mark_completed_task == 1 ? true : false;
            }
        },
    },
    created() {
        this.enableDone = this.sublist.mark_completed_task;
     },
    methods: {
        close() {
            this.$emit('close');
        },

        updateSublistInfo() {
            this.loadingToggle = true;

            const request = {
                mark_completed_task: this.enableDone,
            }
            axios.put(this.route('sublist.update.row', this.sublist.id), request).then((response) => {
                const error  = response?.data?.error;
                if (!error) {
                    this.$emit('onSuccess', this.enableDone);
                }
            

            }).catch((error) => {
                console.log(error)
            }).finally(() => {
                this.loadingToggle = false;
            })
        },
    },
}
</script>