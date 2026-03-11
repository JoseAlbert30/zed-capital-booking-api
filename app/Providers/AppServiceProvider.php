<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register morph map so finance_notes.noteable_type stores short keys
        Relation::morphMap([
            'noc'        => \App\Models\FinanceNOC::class,
            'pop'        => \App\Models\FinancePOP::class,
            'soa'        => \App\Models\FinanceSOA::class,
            'penalty'    => \App\Models\FinancePenalty::class,
            'thirdparty' => \App\Models\FinanceThirdparty::class,
        ]);
    }
}
