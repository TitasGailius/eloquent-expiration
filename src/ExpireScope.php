<?php

namespace TitasGailius\EloquentExpiration;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class ExpireScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $expiredAt = $model->getExpiredAtColumn();

        $builder->where(function (Builder $query) use ($expiredAt) {
            $query->where($expiredAt, '>', Carbon::now())->orWhereNull($expiredAt);
        });
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach (['Expire', 'Unexpire', 'WithExpired', 'OnlyExpired'] as $extension) {
            $this->{'extend' . $extension}($builder);
        }
    }

    /**
     * Extend the query builder with the 'expire' method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extendExpire(Builder $builder)
    {
        $builder->macro('expire', function (Builder $builder) {
            return $builder->update([
                $this->getExpiredAtColumn($builder) => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Extend the query builder with the 'unexpire' method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extendUnexpire(Builder $builder)
    {
        $builder->macro('unexpire', function (Builder $builder) {
            return $builder->withExpired()->update([
                $this->getExpiredAtColumn($builder) => null,
            ]);
        });
    }

    /**
     * Extend the query builder with the 'withExpired' method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extendWithExpired(Builder $builder)
    {
        $builder->macro('withExpired', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Extend the query builder with the 'onlyExpired' method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extendOnlyExpired(Builder $builder)
    {
        $builder->macro('onlyExpired', function (Builder $builder) {
            return $builder->withoutGlobalScope($this)
                ->where($this->getExpiredAtColumn($builder), '<', Carbon::now());
        });
    }

    /**
     * Get the "expired at" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getExpiredAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedExpiredAtColumn();
        }

        return $builder->getModel()->getExpiredAtColumn();
    }
}
