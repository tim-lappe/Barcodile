<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

use App\Infrastructure\Picnic\Domains\App\AppService;
use App\Infrastructure\Picnic\Domains\Auth\AuthService;
use App\Infrastructure\Picnic\Domains\Cart\CartService;
use App\Infrastructure\Picnic\Domains\Catalog\CatalogService;
use App\Infrastructure\Picnic\Domains\Consent\ConsentService;
use App\Infrastructure\Picnic\Domains\Content\ContentService;
use App\Infrastructure\Picnic\Domains\CustomerService\CustomerServiceService;
use App\Infrastructure\Picnic\Domains\Delivery\DeliveryService;
use App\Infrastructure\Picnic\Domains\Payment\PaymentService;
use App\Infrastructure\Picnic\Domains\Recipe\RecipeService;
use App\Infrastructure\Picnic\Domains\User\UserService;
use App\Infrastructure\Picnic\Domains\UserOnboarding\UserOnboardingService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PicnicClient extends PicnicHttpClient
{
    public readonly AppService $app;

    public readonly AuthService $auth;

    public readonly UserService $user;

    public readonly CatalogService $catalog;

    public readonly CartService $cart;

    public readonly DeliveryService $delivery;

    public readonly PaymentService $payment;

    public readonly ConsentService $consent;

    public readonly CustomerServiceService $customerService;

    public readonly ContentService $content;

    public readonly UserOnboardingService $userOnboarding;

    public readonly RecipeService $recipe;

    public function __construct(
        HttpClientInterface $httpClient,
        PicnicApiConfig $config,
        PicnicAuthState $authState,
    ) {
        parent::__construct($httpClient, $config, $authState);
        $this->app = new AppService($this);
        $this->auth = new AuthService($this, $authState);
        $this->user = new UserService($this);
        $this->catalog = new CatalogService($this);
        $this->cart = new CartService($this);
        $this->delivery = new DeliveryService($this);
        $this->payment = new PaymentService($this);
        $this->consent = new ConsentService($this);
        $this->customerService = new CustomerServiceService($this);
        $this->content = new ContentService($this);
        $this->userOnboarding = new UserOnboardingService($this);
        $this->recipe = new RecipeService($this);
    }
}
