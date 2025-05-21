<x-filament::page>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle del evento
        </h2>
    </x-slot>

    {{-- Recaudación global --}}
    <div class="bg-gray-800 p-6 rounded-lg mb-4">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-2xl font-bold text-purple-500">Recaudación global</h3>
                <p class="text-sm text-gray-400"># Unidades vendidas</p>
            </div>
            <div>
                <span class="text-2xl font-bold text-purple-500">$0</span>
                <span class="text-sm text-gray-400">0 de 100</span>
            </div>
        </div>
    </div>

    {{-- Botones principales --}}
    <div class="flex flex-wrap gap-3 mb-6">
        {{-- Botón Editar Stock --}}
        <a href="{{ route('filament.admin.resources.eventos.gestionar-entradas', ['record' => $record->id]) }}"
            class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-edit mr-2"></i> Editar Stock
        </a>

        {{-- Botón Reportes --}}
        <a href="{{ \App\Filament\Resources\EventoResource\Pages\ReportesEvento::getUrl(['record' => $record->id]) }}"
            class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-chart-bar mr-2"></i> Reportes
        </a>

        {{-- Botón Editar Evento --}}
        <a href="{{ route('filament.admin.resources.eventos.edit', ['record' => $record->id]) }}"
            class="bg-blue-600 hover:bg-blue-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-edit mr-2"></i> Editar Evento
        </a>

        {{-- Otros botones --}}
        <a href="#" 
            onclick="event.preventDefault(); copiarAlPortapapeles('{{ route('comprar.entrada', ['evento' => $record->id]) }}')"
            class="btn btn-primary">
            Link
        </a>

                <script>
                function copiarAlPortapapeles(url) {
                    navigator.clipboard.writeText(url)
                        .then(() => alert('URL copiada al portapapeles!'))
                        .catch(() => alert('Error al copiar la URL'));
                }
                </script>


                


        <!-- <button class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-link mr-2"></i> Link
        </button> -->

        <button class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-list-alt mr-2"></i> Lista digital
        </button>

        <button class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-gift mr-2"></i> Enviar productos
        </button>

        <button class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-user-friends mr-2"></i> Productos y cortesías del equipo
        </button>

        <button class="bg-gray-800 hover:bg-gray-700 text-gray font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-gift mr-2"></i> Enviar cortesías
        </button>

        <div>
            <a href="{{ route('mercadopago.connect') }}" class="btn btn-primary">Conectar con Mercado Pago</a>
        </div>

        <div x-data="{ confirmarEliminacion: false, cargando: false }" class="relative">
    <button 
        @click="
            confirmarEliminacion = true;
            cargando = true;
            setTimeout(() => cargando = false, 400);
        "
        class="bg-red-600 hover:bg-red-700 text-gray font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow transition duration-150 ease-in-out"
    >
        <i class="fas fa-trash-alt mr-2"></i> Eliminar Evento
    </button>

    <!-- Overlay -->
    <div 
        x-show="confirmarEliminacion"
        x-transition.opacity.duration.100ms
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm"
    >
        <!-- Modal -->
        <div 
            x-show="confirmarEliminacion"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-90 translate-y-[-140px]"
            x-transition:enter-end="opacity-100 scale-100 translate-y-[-40px]"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 scale-100 translate-y-[-40px]"
            x-transition:leave-end="opacity-0 scale-90 translate-y-[-140px]"
            @click.away="confirmarEliminacion = false"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4"
            style="transform-origin: center top;"
        >
            <template x-if="!cargando">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray">¿Eliminar evento?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Esta acción eliminará el evento permanentemente. ¿Estás seguro de continuar?
                    </p>

                    <div class="flex justify-end gap-3 mt-6">
                        <button 
                            @click="confirmarEliminacion = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray dark:hover:bg-gray-600 transition"
                        >
                            Cancelar
                        </button>

                        <form wire:submit.prevent="eliminarEvento">
                            <button 
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-gray bg-red-600 hover:bg-red-700 rounded-lg transition"
                            >
                                Sí, eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <template x-if="cargando">
                <div class="flex flex-col items-center space-y-4 py-6">
                    <svg class="animate-spin h-8 w-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-gray-600 dark:text-gray-300">Preparando para eliminar...</span>
                </div>
            </template>
        </div>
    </div>
</div>



    </div>
</x-filament::page>
