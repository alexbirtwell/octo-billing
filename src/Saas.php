<?php

namespace OctoBilling;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Saas
{
    /**
     * The list of plans.
     *
     * @var array
     */
    protected static $plans = [];

    /**
     * The list of items with fixed price.
     *
     * @var array
     */
    protected static $items = [];

    /**
     * The callback to call when syncing the current usage.
     *
     * @var array[Closure]
     */
    protected static $syncUsageCallbacks = [];

    /**
     * Specify the global currency.
     *
     * @var string|null
     */
    public static $currency;

    /**
     * Start creating a new plan.
     *
     * @param  string  $name
     * @param  string|int  $id
     * @param  string|int|null  $yearlyId
     * @return \OctoBilling\Plan
     */
    public static function plan(string $name, $id, $yearlyId = null)
    {
        $plan = new Plan($name, $id, $yearlyId);

        static::$plans[] = $plan;

        return $plan;
    }

    /**
     * Start creating a new feature.
     *
     * @param  string  $name
     * @param  string|int  $id
     * @param  int|float  $value
     * @return \OctoBilling\Feature
     */
    public static function feature(string $name, $id, $value = 0, $model = null, $calcule = null)
    {
        return new Feature($name, $id, $value, $model, $calcule);
    }

    /**
     * Start creating a new metered feature.
     *
     * @param  string  $name
     * @param  string|int  $id
     * @param  int|float  $value
     * @return \OctoBilling\MeteredFeature
     */
    public static function meteredFeature(string $name, $id, $value = 0)
    {
        return new MeteredFeature($name, $id, $value);
    }

    /**
     * Assign a new item to the list.
     *
     * @param  string|int  $id
     * @param  string  $name
     * @param  float  $price
     * @param  string  $currency
     * @return \OctoBilling\Item
     */
    public static function item($id, string $name, float $price = 0.00, string $currency = 'EUR')
    {
        $item = new Item($id, $name, $price, Saas::getCurrency($currency));

        static::$items[] = $item;

        return $item;
    }

    /**
     * Add a callback to sync the feature usage.
     *
     * @param  string|int  $id
     * @param  Closure  $callback
     * @return void
     */
    public static function syncFeatureUsage($id, Closure $callback)
    {
        static::$syncUsageCallbacks[$id] = $callback;
    }

    /**
     * Apply the feature usage sync via callback.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $subscription
     * @param  \OctoBilling\Feature  $feature
     * @return int|float|null
     */
    public static function applyFeatureUsageSync(Model $subscription, Feature $feature)
    {
        if ($callback = static::$syncUsageCallbacks[$feature->getId()] ?? null) {
            return call_user_func($callback, $subscription, $feature);
        }
    }

    /**
     * Set the global currency.
     *
     * @param  string  $currency
     * @return void
     */
    public static function currency(string $currency)
    {
        static::$currency = $currency;
    }

    /**
     * Get the global currency if set.
     * Returns a default value if currency is not set.
     *
     * @param  string|null  $default
     * @return string|null
     */
    public static function getCurrency(string $default = null)
    {
        return static::$currency ?: $default;
    }

    /**
     * Get the list of plans.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getPlans()
    {
        return collect(static::$plans);
    }

    /**
     * Get the available plans.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailablePlans()
    {
        return static::getPlans()->filter(function ($plan) {
            return $plan->isActive();
        });
    }

    /**
     * Get a specific plan by id or by yearly ID.
     *
     * @param  \OctoBilling\Plan|string|int|null  $plan
     * @return \OctoBilling\Plan|null
     */
    public static function getPlan($plan)
    {
        if ($plan instanceof Plan) {
            $plan = $plan->getId();
        }

        return collect(static::$plans)->filter(function (Plan $p) use ($plan) {
            return $p->getId() == $plan || $p->getYearlyId() == $plan;
        })->first();
    }

    /**
     * Get the list of items.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getItems()
    {
        return collect(static::$items);
    }

    /**
     * Get a free plan.
     *
      * @param  \OctoBilling\Plan|string|int|null  $plan
     * @return \OctoBilling\Plan|null
     */
    public static function getFreePlan()
    {
        return static::getPlans()->filter(function ($plan) {
            return $plan->isFree();
        })->first();
    }

    /**
     * Get a specific item by id.
     *
     * @param  \OctoBilling\Item|string|int  $item
     * @return \OctoBilling\Item|null
     */
    public static function getItem($item)
    {
        if ($item instanceof Item) {
            $item = $item->getId();
        }

        return collect(static::$items)->filter(function (Item $i) use ($item) {
            return $i->getId() == $item;
        })->first();
    }

    /**
     * Clear the plans.
     *
     * @return void
     */
    public static function clearPlans(): void
    {
        static::$plans = [];
    }

    /**
     * Clear the plans.
     *
     * @return void
     */
    public static function clearItems(): void
    {
        static::$items = [];
    }

    /**
     * Clear the sync usage callbacks.
     *
     * @return void
     */
    public static function cleanSyncUsageCallbacks(): void
    {
        static::$syncUsageCallbacks = [];
    }
}
