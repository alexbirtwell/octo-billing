<?php

namespace OctoBilling\Actions;

use OctoBilling\OctoBilling;
use OctoBilling\Contracts\HandleSubscriptions as HandleSubscriptionsContract;
use OctoBilling\Plan;

class HandleSubscriptions implements HandleSubscriptionsContract
{
    /**
     * Mutate the checkout object before redirecting the user to subscribe to a certain plan.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \OctoBilling\Plan  $plan
     * @return mixed
     */
    public function checkoutOnSubscription($subscription, $billable, Plan $plan)
    {
           return $subscription->checkout([
            'success_url' => URL::to(config('octo.subscription_index')),
            'cancel_url' => URL::to(config('octo.subscription_index')),
         ]);
    }

    /**
     * Subscribe the user to a given plan.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \OctoBilling\Plan  $plan
     * @return void
     */
    public function subscribeToPlan($billable, Plan $plan)
    {
        if ($plan->getId() == config('octo.trial.plan-id', '')) {
            if (!$billable->trial_ends_at === null) {
               abort(403, 'You already have a trial');
            }
            if (config('octo.trial.days', 7) > 0) {
                $billable->trial_ends_at = now()->addDays(config('octo.trial.days', 7));
                $billable->save();
            }
        }
        
        $subscription = $billable->newSubscription($plan->getName(), $plan->getId());

        $meteredFeatures = $plan->getMeteredFeatures();

        if (! $meteredFeatures->isEmpty()) {
            foreach ($meteredFeatures as $feature) {
                $subscription->meteredPrice($feature->getMeteredId());
            }
        }

        $subscription = $subscription->create($billable->defaultPaymentMethod()->id);

        $subscription->stripe_price = $plan->getId();

        $subscription->save();
    }

    /**
     * Swap the current subscription plan.
     *
     * @param  \OctoBilling\Models\Subscription  $subscription
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @param  \OctoBilling\Plan  $plan
     * @return void
     */
    public function swapToPlan($subscription, $billable, Plan $plan)
    {
        if (OctoBilling::proratesOnSwap()) {
            $subscription->swap($plan->getId());
        } else {
            $subscription->noProrate()->swap($plan->getId());
        }
    }

    /**
     * Define the logic to be called when the user requests resuming a subscription.
     *
     * @param  \OctoBilling\Models\Subscription  $subscription
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @return void
     */
    public function resumeSubscription($subscription, $billable)
    {
        $subscription->resume();
    }

    /**
     * Define the subscriptioncancellation action.
     *
     * @param  \OctoBilling\Models\Subscription  $subscription
     * @param  \Illuminate\Database\Eloquent\Model  $billable
     * @return void
     */
    public function cancelSubscription($subscription, $billable)
    {
        $subscription->cancel();
    }
}
