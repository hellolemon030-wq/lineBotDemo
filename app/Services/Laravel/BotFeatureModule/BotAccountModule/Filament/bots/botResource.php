<?php

namespace App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots;

use App\Console\Commands\BotMg;
use App\Models\BotModel;
use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Pages\Createbot;
use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Pages\Editbot;
use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Pages\Listbots;
use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Schemas\botForm;
use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Tables\botsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use bot;

class botResource extends Resource
{
    protected static ?string $model = BotModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'bot';

    public static function form(Schema $schema): Schema
    {
        return botForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return botsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Listbots::route('/'),
            'create' => Createbot::route('/create'),
            'edit' => Editbot::route('/{record}/edit'),
        ];
    }
}
