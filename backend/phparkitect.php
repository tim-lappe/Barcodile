<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App'))
        ->should(new NotImplement('JsonSerializable'))
        ->because('API responses must use explicit serializers or mapped DTOs, not JsonSerializable');

    $rules = array_merge(
        $rules,
        iterator_to_array(
            Architecture::withComponents()
                ->component('Application')->definedBy('App\Application')
                ->component('Domain')->definedBy('App\Domain')
                ->component('Infrastructure')->definedBy('App\Infrastructure')
                ->where('Application')->mayDependOnComponents('Domain')
                ->where('Domain')->shouldNotDependOnAnyComponent()
                ->where('Infrastructure')->mayDependOnComponents('Domain', 'Application')
                ->rules('Application may depend on Domain but not on Infrastructure; Domain must not depend on Application or Infrastructure; Infrastructure may depend on Domain and Application')
        )
    );

    $config->add($classSet, ...$rules);
};
