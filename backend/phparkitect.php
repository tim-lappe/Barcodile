<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');
    $domainNames = array_values(
        array_filter(
            array_map(
                static fn (string $path): string => basename($path),
                glob(__DIR__.'/src/Domain/*', \GLOB_ONLYDIR) ?: []
            ),
            static fn (string $domainName): bool => 'Shared' !== $domainName
        )
    );
    $domainNamespaces = array_map(
        static fn (string $domainName): string => 'App\Domain\\'.$domainName,
        $domainNames
    );

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

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application'))
        ->should(new NotDependsOnTheseNamespaces(
            ['App\Domain'],
            ['App\Domain\*\Facade\*']
        ))
        ->because('Application may call Domain only through domain Facades');

    foreach ($domainNames as $domainName) {
        $domainNamespace = 'App\Domain\\'.$domainName;
        $adapterNamespace = $domainNamespace.'\Adapter';
        $otherDomainNamespaces = array_values(
            array_filter(
                $domainNamespaces,
                static fn (string $candidateNamespace): bool => $candidateNamespace !== $domainNamespace
            )
        );
        $otherDomainFacadeNamespaces = array_map(
            static fn (string $otherDomainNamespace): string => $otherDomainNamespace.'\Facade\*',
            $otherDomainNamespaces
        );
        $otherDomainPortNamespaces = array_map(
            static fn (string $otherDomainNamespace): string => $otherDomainNamespace.'\Port\*',
            $otherDomainNamespaces
        );

        if ([] === $otherDomainNamespaces) {
            continue;
        }

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces($domainNamespace))
            ->andThat(new NotResideInTheseNamespaces($adapterNamespace))
            ->should(new NotDependsOnTheseNamespaces(
                $otherDomainNamespaces,
                $otherDomainFacadeNamespaces
            ))
            ->because('Domains may call other domains only through their Facades');

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces($adapterNamespace))
            ->should(new NotDependsOnTheseNamespaces(
                $otherDomainNamespaces,
                array_merge($otherDomainFacadeNamespaces, $otherDomainPortNamespaces)
            ))
            ->because('Domain Adapters may implement other domains Ports and call other domains through Facades');

        foreach ($otherDomainPortNamespaces as $otherDomainPortNamespace) {
            $rules[] = Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces($domainNamespace))
                ->andThat(new NotResideInTheseNamespaces($adapterNamespace))
                ->should(new NotImplement($otherDomainPortNamespace))
                ->because('Only Domain Adapters may implement Ports owned by another domain');
        }
    }

    $config->add($classSet, ...$rules);
};
