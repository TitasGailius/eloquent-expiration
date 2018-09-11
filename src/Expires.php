<?php

namespace TitasGailius\EloquentExpiration;

use Illuminate\Database\Eloquent\Model;

trait Expires
{
    /**
     * Boot the expires trait for a model.
     *
     * @return void
     */
    public static function bootExpires()
    {
        static::addGlobalScope(new ExpireScope);

        static::$dispatcher->listen('eloquent.booted: ' . static::class, function (Model $model) {
            $model->addObservableEvents(['expiring', 'expired', 'unexpiring', 'unexpired']);
        });
    }

    /**
     * Expire current model instance.
     *
     * @return bool|null
     */
    public function expire()
    {
        if ($this->fireModelEvent('expiring') === false) {
            return false;
        }

        $this->{$this->getExpiredAtColumn()} = $this->freshTimestampString();

        return tap($this->save(), function () {
            $this->fireModelEvent('expired', false);
        });
    }

    /**
     * Unexpire current model instance.
     *
     * @return bool|null
     */
    public function unexpire()
    {
        if ($this->fireModelEvent('unexpiring') === false) {
            return false;
        }

        $this->{$this->getExpiredAtColumn()} = null;

        return tap($this->save(), function () {
            $this->fireModelEvent('unexpired', false);
        });
    }

    /**
     * Get the name of the "expired at" column.
     *
     * @return string
     */
    public function getExpiredAtColumn()
    {
        return defined('static::EXPIRED_AT') ? static::EXPIRED_AT : 'expired_at';
    }

    /**
     * Get the fully qualified "expired at" column.
     *
     * @return string
     */
    public function getQualifiedExpiredAtColumn()
    {
        return $this->qualifyColumn($this->getExpiredAtColumn());
    }

    /**
     * Register an expiring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function expiring($callback)
    {
        static::registerModelEvent('expiring', $callback);
    }

    /**
     * Register an expired model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function expired($callback)
    {
        static::registerModelEvent('expired', $callback);
    }

    /**
     * Register an unexpiring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unexpiring($callback)
    {
        static::registerModelEvent('unexpiring', $callback);
    }

    /**
     * Register an unexpired model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unexpired($callback)
    {
        static::registerModelEvent('unexpired', $callback);
    }
}
