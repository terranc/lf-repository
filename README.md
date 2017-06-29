# lf-repository

## Installation

### Step 1

```shell
composer install terranc/lf-repository --dev
```
### Step 2
In the `appServiceProvider.php`` boot` method inside add:
```php
if ($this->app->environment() == 'local') {
  $this->app->register(\Lookfeel\Repository\RepositoryServiceProvider::class);
}
```

### Step 3

```shell
php artisan vendor:publish --provider "Lookfeel\Repository\RepoistServiceProvider" 
```


## Usage

```shell
php artisan make:repository
```
