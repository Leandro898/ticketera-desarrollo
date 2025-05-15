<?php

namespace App\Filament\Resources\EntradaResource\Pages;

use App\Filament\Resources\EntradaResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Filament\Actions\Action;
use Filament\Tables\Actions\EditAction;

class ListEntradas extends ListRecords
{
    protected static string $resource = EntradaResource::class;

    public function table(Tables\Table $table): Tables\Table
    {
        return parent::table($table)
            ->modifyQueryUsing(function (Builder $query) {
                $eventoId = request()->route('evento_id');
                
                if (!$eventoId) {
                    throw new ModelNotFoundException('Se requiere el ID del evento para listar entradas');
                }
                
                return $query->where('evento_id', $eventoId);
            });
    }

    protected function configureAction(Action $action): void
    {
        $action
            ->url(fn () => EntradaResource::getUrl('create', [
                'evento_id' => request()->route('evento_id')
            ]));
    }

    public function getBreadcrumbs(): array
    {
        $eventoId = request()->route('evento_id');

        return [
            url('/') => __('Home'),
            url('/admin') => __('Dashboard'),
            EntradaResource::getUrl('index', ['evento_id' => $eventoId]) => 'Entradas',
        ];
    }

    public function getTableActions(): array
    {
        return [
            EditAction::make()
                ->url(fn ($record) => static::getResource()::getUrl('edit', [
                    'evento_id' => $record->evento_id,
                    'record' => $record->id,
                ])),
        ];
    }
}