<template>
<div class="flex flex-col h-full bg-gray-50 rounded-lg border border-gray-200">

    <!-- Header -->
    <div class="px-4 py-3 border-b bg-white rounded-t-lg">
        <h2 class="text-sm font-semibold text-gray-700">
            Conversación
        </h2>
    </div>

    <!-- Messages -->
    <div ref="chatContainer"
        class="flex-1 overflow-y-auto p-4 space-y-3">

        <div
            v-for="(msg, index) in messages"
            :key="index"
            class="flex"
            :class="msg.mine ? 'justify-end' : 'justify-start'"
        >

            <div
                class="max-w-[70%] px-3 py-2 rounded-lg text-sm shadow-sm"
                :class="msg.mine
                    ? 'bg-blue-500 text-white rounded-br-none'
                    : 'bg-white border text-gray-700 rounded-bl-none'"
            >
                <p>{{ msg.text }}</p>

                <span class="block text-[10px] opacity-70 mt-1 text-right">
                    {{ msg.time }}
                </span>
            </div>

        </div>

    </div>

    <!-- Input -->
    <div class="border-t bg-white p-3 flex gap-2">

        <input
            v-model="newMessage"
            @keyup.enter="sendMessage"
            type="text"
            placeholder="Escribe un mensaje..."
            class="flex-1 text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-400"
        />

        <button
            @click="sendMessage"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm"
        >
            Enviar
        </button>

    </div>

</div>
</template>

<script>
export default {
    name: "ChatBox",

    data() {
        return {
            newMessage: "",
            messages: [
                {
                    text: "Hola, cual es el estatus de la tarea?",
                    mine: false,
                    time: "10:10"
                },
                {
                    text: "Hola, ¿en qué vas con la tarea?",
                    mine: true,
                    time: "10:11"
                }
            ]
        }
    },

    methods: {
        sendMessage() {

            if (!this.newMessage.trim()) return

            this.messages.push({
                text: this.newMessage,
                mine: true,
                time: new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                })
            })

            this.newMessage = ""

            this.$nextTick(() => {
                this.scrollToBottom()
            })
        },

        scrollToBottom() {
            const container = this.$refs.chatContainer
            container.scrollTop = container.scrollHeight
        }
    },

    mounted() {
        this.scrollToBottom()
    }
}
</script>