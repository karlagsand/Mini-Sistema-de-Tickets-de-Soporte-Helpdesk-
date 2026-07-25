<x-app-layout>
    @php
        $currentUser = auth()->user();
        $isRequester = $currentUser->isUserRole();
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">{{ $isRequester ? 'Registrar solicitud' : 'Registrar ticket' }}</h1>
                <p class="page-subtitle">
                    {{ $isRequester ? 'Cuéntanos qué necesitas para dar seguimiento a tu caso.' : 'Captura la información inicial del caso.' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('tickets.index') }}" class="app-btn-secondary">{{ $isRequester ? 'Mis solicitudes' : 'Volver a tickets' }}</a>
                <a href="{{ route('dashboard') }}" class="app-btn-ghost">Inicio</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if ($errors->any())
                <div class="flash-error" role="alert" aria-live="polite">
                    <p class="font-semibold mb-2">Revisa la información:</p>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 app-card p-6">
                    <div class="mb-6">
                        <h2 class="section-title">Datos de la solicitud</h2>
                        <p class="text-soft text-sm">
                            Completa la información básica para que podamos registrar y dar seguimiento a tu solicitud.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <label for="subject" class="form-label">Asunto</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="150" required aria-describedby="subject_help">
                            <p id="subject_help" class="form-help">Escribe un título breve. Ejemplo: “No puedo ingresar al correo”.</p>
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category_id" class="form-label">Área relacionada</label>
                                <select id="category_id" name="category_id" required aria-describedby="category_help">
                                    <option value="">Selecciona una opción</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p id="category_help" class="form-help">Elige la opción más cercana. Si no sabes cuál elegir, usa “No estoy seguro”.</p>
                                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="request_type" class="form-label">Tipo de apoyo</label>
                                <select id="request_type" name="request_type" required>
                                    @foreach($requestTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('request_type', 'incidente') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="form-help">Selecciona lo que mejor describa tu caso.</p>
                                <x-input-error :messages="$errors->get('request_type')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <label for="reported_impact" class="form-label">¿Cómo te afecta?</label>
                            <select id="reported_impact" name="reported_impact">
                                <option value="">Selecciona una opción</option>
                                @foreach($reportedImpactOptions as $key => $label)
                                    <option value="{{ $key }}" {{ old('reported_impact') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="form-help">Ayuda a entender el alcance de la solicitud.</p>
                            <x-input-error :messages="$errors->get('reported_impact')" class="mt-2" />
                        </div>

                        <div>
                            <label for="description" class="form-label">Descripción</label>
                            <textarea id="description" name="description" rows="8" required aria-describedby="description_help">{{ old('description') }}</textarea>
                            <p id="description_help" class="form-help">Incluye qué sucede, desde cuándo ocurre y cualquier mensaje de error que aparezca.</p>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="initial-attachments-panel" data-support-uploader data-max-files="5" data-max-size-mb="10">
                            <div>
                                <label for="support_files" class="form-label">Archivos de soporte <span class="text-soft font-normal">(opcional)</span></label>
                                <input
                                    id="support_files"
                                    name="support_files[]"
                                    type="file"
                                    multiple
                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                    class="support-file-input"
                                    aria-describedby="support_files_help support_files_counter"
                                    data-support-file-input
                                >
                                <p id="support_files_help" class="form-help">Puedes seleccionar archivos en diferentes momentos antes de enviar. El sistema conservará la lista acumulada hasta un máximo de 5 archivos de 10 MB cada uno: imágenes, PDF, Word, Excel o TXT.</p>
                                <div class="support-file-status" aria-live="polite">
                                    <span id="support_files_counter" data-support-file-counter>0 de 5 archivos seleccionados</span>
                                    <span data-support-file-message></span>
                                </div>
                                <div class="support-file-list" data-support-file-list></div>
                                <x-input-error :messages="$errors->get('support_files')" class="mt-2" />
                                <x-input-error :messages="$errors->get('support_files.*')" class="mt-2" />
                            </div>
                            <div class="evidence-note">
                                <p class="font-semibold text-[var(--text-main)]">Importante sobre la evidencia</p>
                                <p class="text-sm text-soft mt-1">Estos archivos se guardarán como parte del registro inicial de la solicitud. Después de enviarla, no podrás agregar ni eliminar archivos desde este seguimiento para conservar la evidencia original del caso.</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('tickets.index') }}" class="app-btn-secondary">Cancelar</a>
                            <button type="submit" class="app-btn-primary">Enviar solicitud</button>
                        </div>
                    </form>
                </div>

                <aside class="app-card p-6">
                    <h2 class="section-title">Antes de enviar</h2>
                    <div class="space-y-4 text-sm text-soft">
                        <div class="info-panel">
                            <p class="info-label">Describe el caso</p>
                            <p class="info-value">Indica qué necesitas o qué no funciona para que el seguimiento sea más rápido.</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Agrega contexto</p>
                            <p class="info-value">Menciona si afecta a una persona, a un equipo o a varias áreas.</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Adjunta evidencia antes de enviar</p>
                            <p class="info-value">Si tienes capturas o documentos, súbelos ahora. Después no podrán modificarse desde la solicitud.</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Consulta el seguimiento</p>
                            <p class="info-value">Después de enviar la solicitud, podrás ver actualizaciones desde “Mis solicitudes”.</p>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const uploader = document.querySelector('[data-support-uploader]');

            if (!uploader || typeof DataTransfer === 'undefined') {
                return;
            }

            const input = uploader.querySelector('[data-support-file-input]');
            const list = uploader.querySelector('[data-support-file-list]');
            const counter = uploader.querySelector('[data-support-file-counter]');
            const message = uploader.querySelector('[data-support-file-message]');
            const maxFiles = Number.parseInt(uploader.dataset.maxFiles || '5', 10);
            const maxSizeMb = Number.parseInt(uploader.dataset.maxSizeMb || '10', 10);
            const maxSizeBytes = maxSizeMb * 1024 * 1024;
            let selectedFiles = [];

            const fileKey = (file) => [file.name, file.size, file.lastModified].join('|');

            const formatSize = (bytes) => {
                if (bytes >= 1024 * 1024) {
                    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
                }

                return `${Math.max(1, Math.round(bytes / 1024))} KB`;
            };

            const syncInput = () => {
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach((file) => dataTransfer.items.add(file));
                input.files = dataTransfer.files;
            };

            const render = () => {
                syncInput();
                list.innerHTML = '';
                counter.textContent = `${selectedFiles.length} de ${maxFiles} archivo${maxFiles === 1 ? '' : 's'} seleccionado${selectedFiles.length === 1 ? '' : 's'}`;

                if (selectedFiles.length === 0) {
                    list.innerHTML = '<p class="support-file-empty">Aún no has seleccionado archivos de soporte.</p>';
                    return;
                }

                selectedFiles.forEach((file, index) => {
                    const row = document.createElement('div');
                    row.className = 'support-file-item';
                    row.innerHTML = `
                        <div class="support-file-meta">
                            <span class="support-file-name"></span>
                            <span class="support-file-size">${formatSize(file.size)}</span>
                        </div>
                        <button type="button" class="support-file-remove" data-index="${index}" aria-label="Quitar archivo ${index + 1}">Quitar</button>
                    `;
                    row.querySelector('.support-file-name').textContent = file.name;
                    list.appendChild(row);
                });
            };

            input.addEventListener('change', () => {
                message.textContent = '';
                const existingKeys = new Set(selectedFiles.map(fileKey));

                Array.from(input.files || []).forEach((file) => {
                    if (selectedFiles.length >= maxFiles) {
                        message.textContent = `Solo puedes adjuntar hasta ${maxFiles} archivos.`;
                        return;
                    }

                    if (file.size > maxSizeBytes) {
                        message.textContent = `Se omitió "${file.name}" porque supera ${maxSizeMb} MB.`;
                        return;
                    }

                    if (existingKeys.has(fileKey(file))) {
                        message.textContent = `"${file.name}" ya estaba en la lista.`;
                        return;
                    }

                    selectedFiles.push(file);
                    existingKeys.add(fileKey(file));
                });

                render();
            });

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-index]');

                if (!button) {
                    return;
                }

                selectedFiles.splice(Number.parseInt(button.dataset.index, 10), 1);
                message.textContent = '';
                render();
            });

            render();
        });
    </script>

</x-app-layout>
