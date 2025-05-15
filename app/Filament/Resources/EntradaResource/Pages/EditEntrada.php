<?php

namespace App\Filament\Resources\EntradaResource\Pages;

use Filament\Actions;
use App\Filament\Resources\EntradaResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\RedirectResponse;
use Filament\Resources\Pages\CreateRecord;

class EditEntrada extends EditRecord
{
    protected static string $resource = EntradaResource::class;

    public function afterUpdate(): RedirectResponse
    {
        // Verifica que el evento_id esté correctamente pasando
        $evento_id = $this->record->evento_id;

        // Asegúrate de pasar el evento_id correctamente en la redirección
        return redirect()->route('filament.resources.entradas.index', [
            'evento_id' => $evento_id, // Aquí pasamos el evento_id correctamente
        ]);
    }

    public function getRedirectUrl(): string
    {
        return route('filament.admin.resources.eventos.view', [
            'record' => $this->record->evento_id,
        ]) . '#relationManagerComponent=entradas';
    }

    public function getBreadcrumbs(): array
    {
        $eventoId = request()->route('evento_id');

        if (!$eventoId) {
            return [
                url('/') => __('Home'),
                url('/admin') => __('Dashboard'),
                EntradaResource::getUrl('index') => 'Entradas',
            ];
        }

        return [
            url('/') => __('Home'),
            url('/admin') => __('Dashboard'),
            EntradaResource::getUrl('index', ['evento_id' => $eventoId]) => 'Entradas',
        ];
    }
    
}
