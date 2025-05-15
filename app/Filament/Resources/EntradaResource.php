<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntradaResource\Pages;
use App\Models\Entrada;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EntradaResource extends Resource
{
    protected static ?string $model = Entrada::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('evento_id')
                    ->required()
                    ->default(function () {
                        $eventoId = request()->query('ownerRecord') ?? request()->route('evento_id');
                        
                        if (!$eventoId) {
                            throw new ModelNotFoundException('El ID del evento es requerido');
                        }

                        return $eventoId;
                    }),
                
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('descripcion')
                    ->columnSpanFull(),
                
                Forms\Components\TextInput::make('stock_inicial')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                
                Forms\Components\TextInput::make('stock_actual')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                
                Forms\Components\TextInput::make('max_por_compra')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                
                Forms\Components\TextInput::make('precio')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                
                Forms\Components\DateTimePicker::make('disponible_desde'),
                Forms\Components\DateTimePicker::make('disponible_hasta'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $eventoId = request()->route('evento_id');
                
                if (!$eventoId) {
                    throw new \RuntimeException('Se requiere el ID del evento');
                }
                
                return $query->where('evento_id', $eventoId);
            })
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('stock_inicial')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('stock_actual')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock_actual <= 0 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('max_por_compra')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('precio')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),
                
                Tables\Columns\TextColumn::make('disponible_desde')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('disponible_hasta')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Filtros adicionales pueden ir aquí
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relaciones pueden ir aquí
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntradas::route('/{evento_id}'),
            'create' => Pages\CreateEntrada::route('/{evento_id}/create'),
            'edit' => Pages\EditEntrada::route('/{evento_id}/{record}/edit'),
            'view' => Pages\ViewEntrada::route('/{evento_id}/{record}'),
        ];
    }
}