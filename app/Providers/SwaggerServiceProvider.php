<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

class SwaggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        config([
            'l5-swagger.defaults.scanOptions.analyser' => new ReflectionAnalyser([
                new AttributeAnnotationFactory,
                new DocBlockAnnotationFactory,
            ]),
        ]);
    }
}
