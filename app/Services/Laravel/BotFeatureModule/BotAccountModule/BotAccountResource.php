<?php

namespace App\Services\Laravel\BotFeatureModule\BotAccountModule;

use App\Filament\Admin\Resources\Tests\Pages\ListTests;
use App\Models\BotAccount; // 确保这里引用了您正确的 Model 路径
use App\Models\BotModel;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;



class BotAccountResource extends Resource
{
    // 指定使用的 Model
    protected static ?string $model = BotModel::class;

    // protected static $navigationIcon = Heroicon::AcademicCap;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = '机器人账户管理';

    // 可选：自定义左侧导航栏图标

    // 定义表单字段（需要根据您的 BotAccount 模型字段进行调整）

    // 定义表格列（需要根据您的 BotAccount 模型字段进行调整）
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    // 定义关联的页面（这是关键）
    public static function getPages(): array
    {
        return [
        ];
    }
    
    // 如果需要，可以定义一个通用的 getRelations 方法
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}