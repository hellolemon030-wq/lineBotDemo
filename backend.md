







./vendor/bin/sail artisan make:filament-resource 

1. filamend;
2. ./vendor/bin/sail php artisan filament:install --panels

3. 核心，moduleBase::_needFilamentSupport
  - moduleManager->_initModuleFilament2Panel

4. ...