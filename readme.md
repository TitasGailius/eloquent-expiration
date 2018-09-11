# Eloquent models with an expiration date

This package allow you to add an expiration date to your Eloquent models.

# Installation

```
composer require titasgailius/eloquent-expiration
```

Next, add `Titasgailius\EloquentExpiration\Expires` trait to any of your eloquent model.
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Titasgailius\EloquentExpiration\Expires;

class Post extends Model
{
    use Expires;
}
```

**Setup a database column to store an expiration date.**
```php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
        });
    }
}
```
By default, this package looks for an `expired_at` column but you may easily [change it.](#customization)


# Usage

**Expired models will be excluded automatically.**

```php
$posts = Post::all(); // Posts that are not expired.
```

**Including expired models**
```php
$posts = Post::withExpired()->all(); // Posts including expired ones.
```

**Retrieving expired models**
```php
$posts = Post::onlyExpired()->all(); // Posts including expired ones.

```
**Updating many records**
```php
Post::whereDate('last_comment', '<', '2018-01-01')->expire();
Post::where('comment_count', '>', 100)->unexpire();
```
Note: When updating many records Eloquent events will not be fired.


## Models

You may `expire` and `unexpire` your Eloquent models.
```php
$post->expire();
$post->unexpire();
```

## Events

Eloquent models with an expiration date fire several events, allowing you to hook into the following points in a model's lifecycle:
`expiring`, `expired`, `unexpiring`, `unexpired`.
Events allow you to easily execute code each time a specific model class is epxired or unexpired.
To get started, define a `$dispatchesEvents` property on your Eloquent model that maps various points of the Eloquent model's lifecycle to your own event classes:
```php
<?php

namespace App;

use App\Events\PostExpired;
use App\Events\PostExpiring;
use Illuminate\Database\Eloquent\Model;
use Titasgailius\EloquentExpiration\Expires;

class Post extends Model
{
    use Expires;

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'expiring' => PostExpiring::class,
        'expired' => PostExpired::class,
    ];
}
```

## Model observers

You may also use observers to group all of your listeners into a single class.
```php
<?php

namespace App\Observers;

use App\Post;

class PostObserver
{
    /**
     * Handle to the Post "expired" event.
     *
     * @param  \App\Post  $user
     * @return void
     */
    public function expired(Post $post)
    {
        //
    }

    /**
     * Handle to the Post "unexpired" event.
     *
     * @param  \App\Post  $user
     * @return void
     */
    public function unexpired(Post $post)
    {
        //
    }
}
```

To register an observer, use the `observe` method on the model you wish to observe.
You may register observers in the boot method of one of your service providers.
In this example, we'll register the observer in the `AppServiceProvider`:
```php
<?php

namespace App\Providers;

use App\Post;
use App\Observers\PostObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Post::observe(PostObserver::class);
    }
}
```

# Customization
You may change the column name that is used to store expiration date by specifying `EXPIRED_AT` constant.
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Titasgailius\EloquentExpiration\Expires;

class Post extends Model
{
    use Expires;

    const EXPIRED_AT = 'expires_at';
}
```
