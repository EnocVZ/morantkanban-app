<template>
    <div v-for="(log, log_i) in modelValue" :key="log_i + log" class="group relative flex py-1">
        <div class="group flex-1 ltr:pl-4 rtl:pr-4">
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <span class="font-medium text-gray-900">{{
                    formatDate(log.started_at) }}</span>-
                <span class="font-medium text-gray-900">{{
                    formatDate(log.stopped_at) }}</span>
                <span class="ml-auto text-gray-400 text-xs font-semibold">{{
                    moment.duration(log.duration, 'seconds').format('h[h] m[m] s[s]') }}</span>
                <span>
                    <icon class="w-4 h-4 cursor-pointer" name="edit" @click="openEditLogTime(log)" />
                </span>
                <span>
                    <icon class="w-4 h-4 cursor-pointer" name="trash" @click="openDeleteLogTime(log)" />
                </span>
            </div>
        </div>
    </div>
    <confirm-dialog v-model="dialogConfirm"
    title="Eliminar el registro de tiempo"
    message="¿Seguro que deseas eliminar este registro de tiempo?"
    @confirm="onConfirmDelete"
    :loaderConfirm="loaderConfirm"/>
</template>
<script>
import moment from 'moment'
import 'moment-duration-format';
import Icon from '@/Shared/Icon'
import ConfirmDialog from "@/Shared/Modals/ConfirmDialog";
import axios from 'axios'

export default {
    components: {
        Icon, ConfirmDialog,

    },
    props: {
        modelValue: Array,
        task: Object,
    },
    emits: ['update:modelValue', 'onEdit'],
    data() {
        return {
            openLogTime: false,
            timerToUpdate: {},
            moment: moment,
            dialogConfirm: false,
            loaderConfirm: false,
        }
    },
    methods: {
        formatDate(date) {
            return this.moment(date).format('MMM D, YYYY h:mm A');
        },
        openEditLogTime(log) {
            this.$emit('onEdit', log);
        },
        openDeleteLogTime(log) {
            this.timerToUpdate = log;
            this.dialogConfirm = true;
        },

        onConfirmDelete(){
            this.loaderConfirm = true;
            axios.delete(route('task.timer.delete', this.timerToUpdate.id))
                .then((response) => {
                    const data = response.data;
                    if(!data.error){
                        const timerList = this.modelValue.filter(log => log.id !== this.timerToUpdate.id);
                        this.$emit('update:modelValue', timerList);
                        this.dialogConfirm = false;
                        this.$toast.open({
                            message: 'Registro de tiempo eliminado correctamente',
                            type: 'success',
                        });
                    }else{
                        this.$toast.open({
                            message: 'Problemas al eliminar el registro de tiempo',
                            type: 'error',
                        });
                    }
                   
                })
                .catch(() => {
                   this.$toast.open({
                    message: 'Problemas al eliminar el registro de tiempo',
                    type: 'error',
                    });
                }).finally(() => {
                    this.loaderConfirm = false;
                })
        }
    },
}
</script>