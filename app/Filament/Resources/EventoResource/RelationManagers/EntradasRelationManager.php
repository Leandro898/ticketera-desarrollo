<?php

namespace App\Filament\Resources\EventoResource\RelationManagers;

use App\Filament\Resources\EntradaResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\EditAction;


class EntradasRelationManager extends RelationManager
{
    protected static string $relationship = 'entradas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre'),
                Tables\Columns\TextColumn::make('precio'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('crear_entrada')
                    ->label('Crear Entrada')
                    ->url(fn () => EntradaResource::getUrl('create', [
                        'evento_id' => $this->ownerRecord->id,
                    ]))
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            ])

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\EntradaResource::getUrl('view', [
                        'record' => $record,
                        'evento_id' => $this->ownerRecord->id,
                    ])),
                
                    EditAction::make()
                        ->url(fn ($record) => \App\Filament\Resources\EntradaResource::getUrl('edit', [
                            'record' => $record->id,
                            'evento_id' => $this->ownerRecord->id,
                        ])),
                // Tables\Actions\Action::make('view')
                //     ->label('Ver')
                //     ->url(fn ($record) => EntradaResource::getUrl('view', [
                //         'record' => $record->id,
                //         'evento_id' => $this->getOwnerRecord()->id
                //     ]))
                //     ->icon('heroicon-o-eye'),
            ]);
    }
    
}
