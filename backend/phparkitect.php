<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');
    $contextNames = array_values(
        array_filter(
            array_map(
                static fn (string $path): string => basename($path),
                glob(__DIR__.'/src/*', \GLOB_ONLYDIR) ?: []
            ),
            static fn (string $contextName): bool => 'SharedKernel' !== $contextName
                && is_dir(__DIR__.'/src/'.$contextName.'/Domain')
        )
    );
    $contextNamespaces = array_map(
        static fn (string $contextName): string => 'App\\'.$contextName,
        $contextNames
    );
    $apiNamespaces = array_map(
        static fn (string $contextNamespace): string => $contextNamespace.'\Api',
        $contextNamespaces
    );

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App'))
        ->should(new NotImplement('JsonSerializable'))
        ->because('API responses must use explicit serializers or mapped DTOs, not JsonSerializable');

    foreach ($contextNames as $contextName) {
        $contextNamespace = 'App\\'.$contextName;
        $domainNamespace = $contextNamespace.'\Domain';
        $adapterNamespace = $domainNamespace.'\Adapter';
        $otherContextNamespaces = array_values(
            array_filter(
                $contextNamespaces,
                static fn (string $candidateNamespace): bool => $candidateNamespace !== $contextNamespace
            )
        );
        $otherDomainNamespaces = array_map(
            static fn (string $otherContextNamespace): string => $otherContextNamespace.'\Domain',
            $otherContextNamespaces
        );
        $otherApplicationNamespaces = array_map(
            static fn (string $otherContextNamespace): string => $otherContextNamespace.'\Application',
            $otherContextNamespaces
        );
        $otherInfrastructureNamespaces = array_map(
            static fn (string $otherContextNamespace): string => $otherContextNamespace.'\Infrastructure',
            $otherContextNamespaces
        );
        $ownNonDomainNamespaces = [
            $contextNamespace.'\Api',
            $contextNamespace.'\Application',
            $contextNamespace.'\Infrastructure',
        ];
        $otherDomainFacadeNamespaces = array_map(
            static fn (string $otherDomainNamespace): string => $otherDomainNamespace.'\Facade\*',
            $otherDomainNamespaces
        );
        $otherDomainPortNamespaces = array_map(
            static fn (string $otherDomainNamespace): string => $otherDomainNamespace.'\Port\*',
            $otherDomainNamespaces
        );

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces($domainNamespace))
            ->should(new NotDependsOnTheseNamespaces(
                array_merge($ownNonDomainNamespaces, $otherApplicationNamespaces, $otherInfrastructureNamespaces)
            ))
            ->because('Context Domain code must not depend on API, Application, or Infrastructure layers');

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces($domainNamespace))
            ->andThat(new NotResideInTheseNamespaces($adapterNamespace))
            ->should(new NotDependsOnTheseNamespaces(
                $otherDomainNamespaces,
                $otherDomainFacadeNamespaces
            ))
            ->because('Domains may call other domains only through their Facades');

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces($contextNamespace.'\Application'))
            ->should(new NotDependsOnTheseNamespaces(
                array_merge([$contextNamespace.'\Infrastructure'], $otherInfrastructureNamespaces)
            ))
            ->because('Application services may orchestrate contexts but must not depend on Infrastructure');

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces($contextNamespace.'\Api'))
            ->should(new NotDependsOnTheseNamespaces(
                array_merge([$contextNamespace.'\Infrastructure'], $otherInfrastructureNamespaces)
            ))
            ->because('API adapters may call Application services but must not depend on Infrastructure');

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces(
                $contextNamespace.'\Application',
                $domainNamespace,
                $contextNamespace.'\Infrastructure'
            ))
            ->should(new NotDependsOnTheseNamespaces($apiNamespaces))
            ->because('Application, Domain, and Infrastructure layers must not depend on API adapters');

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
