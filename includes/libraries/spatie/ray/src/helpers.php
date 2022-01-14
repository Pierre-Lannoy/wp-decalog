<?php

use Illuminate\Contracts\Container\BindingResolutionException;
use DLSpatie\CraftRay\Ray as CraftRay;
use DLSpatie\LaravelRay\Ray as LaravelRay;
use DLSpatie\Ray\Ray;

use DLSpatie\Ray\Settings\SettingsFactory;
use DLSpatie\RayBundle\Ray as SymfonyRay;
use DLSpatie\WordPressRay\Ray as WordPressRay;
use DLSpatie\YiiRay\Ray as YiiRay;

if (! function_exists('ray')) {
    /**
     * @param mixed ...$args
     *
     * @return \DLSpatie\Ray\Ray|LaravelRay|WordPressRay|YiiRay|SymfonyRay
     */
    function ray(...$args)
    {
        if (class_exists(LaravelRay::class)) {
            try {
                return app(LaravelRay::class)->send(...$args);
            } catch (BindingResolutionException $exception) {
                // this  exception can occur when requiring spatie/ray in an Orchestra powered
                // testsuite without spatie/laravel-ray's service provider being registered
                // in `getPackageProviders` of the base test suite
            }
        }

        if (class_exists(CraftRay::class)) {
            return Yii::$container->get(CraftRay::class)->send(...$args);
        }

        if (class_exists(YiiRay::class)) {
            return Yii::$container->get(YiiRay::class)->send(...$args);
        }

        $rayClass = Ray::class;

        if (class_exists(WordPressRay::class)) {
            $rayClass = WordPressRay::class;
        }

        if (class_exists(SymfonyRay::class)) {
            $rayClass = SymfonyRay::class;
        }

        $settings = SettingsFactory::createFromConfigFile();

        return (new $rayClass($settings))->send(...$args);
    }

    register_shutdown_function(function () {
        ray()->throwExceptions();
    });
}

if (! function_exists('rd')) {
    function rd(...$args)
    {
        ray(...$args)->die();
    }
}
