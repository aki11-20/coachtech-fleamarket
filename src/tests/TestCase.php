<?php

namespace Tests;

use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Mockery\MockInterface;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** @var MockInterface */
    protected $stripePaymentServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripePaymentServiceMock = Mockery::mock(StripePaymentService::class);
        $this->app->instance(StripePaymentService::class, $this->stripePaymentServiceMock);
    }
}
