<?php

namespace App\Filament\Resources\EntradaResource\Pages;

use App\Filament\Resources\EntradaResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEntrada extends ViewRecord
{
    protected static string $resource = EntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aquí puedes añadir acciones si las necesitas
        ];
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