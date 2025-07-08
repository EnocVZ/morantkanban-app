<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sección azul superior -->
    <div class="bg-blue-600 min-h-[30vh] px-4 flex items-center" :style="{backgroundImage: 'url(/images/gradients/1.svg)'}" >
     
    </div>

    <!-- Formulario con fondo blanco -->
    <div class="max-w-2xl mx-auto -mt-32 bg-white rounded-xl shadow-lg p-8 relative z-10">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 mt-2 flex items-center gap-2">
          <span class="text-black text-3xl">❗</span> Cree una solicitud
        </h1>
      </div>
      <form class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tu correo*</label>
          <input type="email" placeholder="solicitante@correo.com"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <!-- Campo resumen -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Titulo *</label>
          <input type="text" placeholder="Titulo de la solicitud"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Descripción -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
          <textarea rows="5" placeholder="Describa su solicitud"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>

        <!-- Environment -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo solicitud</label>
          <select
            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>Ayuda</option>
            <option>Bug</option>
            <option>Solicitud cambio</option>
          </select>
        </div>
        <div
            class="flex justify-center items-center w-full"
            >
            <label
                for="file-upload"
                class="flex flex-col justify-center items-center w-full h-32 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:border-blue-500"
            >
                <svg
                class="w-8 h-8 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16v-4a4 4 0 014-4h3m4 4l-5-5m0 0l-5 5m5-5v12"
                />
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                <span class="font-medium">Haz clic para subir</span> o arrastra y suelta
                </p>
                <input id="file-upload" type="file" class="hidden" />
            </label>
            </div>
       
        <!-- Botones -->
        <div class="flex items-center gap-4">
          <button type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">Enviar</button>
        </div>
      </form>
    </div>

   
  </div>
</template>
