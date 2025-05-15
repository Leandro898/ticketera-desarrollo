<?php

namespace App\Filament\Resources\EntradaResource\Pages;

use App\Filament\Resources\EventoResource;
use App\Filament\Resources\EntradaResource;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;

class CreateEntrada extends CreateRecord
{
    protected static string $resource = EntradaResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Hidden::make('evento_id')
                    ->default(fn () => request()->route('evento_id')),

                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),

                Textarea::make('descripcion')
                    ->columnSpanFull(),

                TextInput::make('precio')
                    ->numeric()
                    ->required(),

                TextInput::make('stock_inicial')
                    ->numeric()
                    ->required()
                    ->minValue(1),

                TextInput::make('stock_actual')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0),

                TextInput::make('max_por_compra')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['evento_id'])) {
            throw new \RuntimeException('Se requiere un evento_id para crear la entrada.');
        }

        return $data;
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

    protected function getRedirectUrl(): string
    {
        $eventoId = request()->route('evento_id') ?? $this->record->evento_id;

        Notification::make()
            ->title('Entrada creada')
            ->success()
            ->send();

        if (!$eventoId) {
            return EventoResource::getUrl('index');
        }

        return EventoResource::getUrl('edit', ['record' => $eventoId]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // ← No muestra título
    }
    // protected function getRedirectUrl(): string
    // {
    //     // Obtiene el evento_id del registro creado o de la URL
    //     $eventoId = $this->record->evento_id ?? request()->route('evento_id');

    //     if (!$eventoId) {
    //         // Si no hay evento_id, puedes redirigir a otra página
    //         return static::getResource()::getUrl('index');
    //     }

    //     return static::getResource()::getUrl('index', ['evento_id' => $eventoId]);
    // }
}