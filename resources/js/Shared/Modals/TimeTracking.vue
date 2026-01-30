<template>
  <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-6 space-y-4">
    <!-- Header 
    <div>
      <h2 class="text-lg font-semibold text-slate-800">Time tracking</h2>
      <p class="text-sm text-slate-500">
        2d 2h logged · Original estimate for this issue was
        <span class="text-indigo-600 font-medium">2d</span>
      </p>
    </div>
  -->
    <!-- Progress
    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
      <div class="h-full bg-indigo-500 w-[80%]"></div>
    </div>
 -->
    <!-- Time inputs -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="text-xs text-slate-500">Tiempo empleado en horas</label>
        <input
          v-model="form.spent"
          class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          placeholder=""
        />
      </div>

      <!--div>
        <label class="text-xs text-slate-500">Time remaining</label>
        <input
          v-model="form.remaining"
          class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          placeholder="0m"
        />
      </div-->
    </div>

    <!-- Date -->
    <div>
      <label class="text-xs text-slate-500">Fecha de inicio</label>
      <div class="flex gap-2 mt-1">
        <input
          type="date"
          v-model="form.date"
          class="h-10 w-full rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
        <input
          type="time"
          v-model="form.time"
          class="h-10 w-28 rounded-lg border border-slate-300 px-3
                 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        />
      </div>
    </div>

    <!-- Work description -->
    <!--div>
      <label class="text-xs text-slate-500">Descripción del trabajo</label>

      <div class="flex gap-2 text-slate-500 text-sm mt-1 mb-1">
        <span class="font-bold">A</span>
        <span class="italic">B</span>
        <span class="underline">U</span>
        <span>•</span>
        <span>≡</span>
      </div>

      <textarea
        v-model="form.description"
        rows="3"
        class="w-full rounded-lg border border-slate-300 px-3 py-2
               focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        placeholder="Describe en lo que has trabajado..."
      />
    </div-->

    <!-- Footer -->
    <div class="flex justify-end gap-3 pt-2">
      <button
        class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-100">
        Cancel
      </button>

      <button
        class="px-4 py-2 rounded-lg bg-indigo-600 text-white
               hover:bg-indigo-700 shadow-sm">
        Save
      </button>
    </div>
  </div>
</template>
<script setup>
import { reactive } from 'vue'

const form = reactive({
  spent: '',
  remaining: '',
  date: '',
  time: '',
  description: '',
})
</script>