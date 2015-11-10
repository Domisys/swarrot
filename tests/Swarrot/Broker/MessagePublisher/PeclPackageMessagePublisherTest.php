<?php

namespace Swarrot\Broker\MessagePublisher;

use Swarrot\Broker\Message;
use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Argument;

class PeclPackageMessagePublisherTest extends ProphecyTestCase
{
    protected function setUp()
    {
        if (!class_exists('AMQPConnection')) {
            $this->markTestSkipped('The AMQP extension is not available');
        }
        parent::setUp();
    }

    public function test_publish_with_valid_message()
    {
        $exchange = $this->prophesize('\AMQPExchange');
        $exchange
            ->publish(
                Argument::exact('body'),
                Argument::exact(null),
                Argument::exact(0),
                Argument::exact([])
            )
            ->shouldBeCalledTimes(1)
        ;

        $provider = new PeclPackageMessagePublisher($exchange->reveal());
        $return = $provider->publish(
            new Message('body')
        );

        $this->assertNull($return);
    }
    
    public function test_it_should_remove_nested_arrays_from_headers()
    {
        $exchange = $this->prophesize('\AMQPExchange');
        $exchange
            ->publish(
                Argument::exact('body'),
                Argument::exact(null),
                Argument::exact(0),
                Argument::exact([
                    'headers' => [
                        'header' => 'value',
                        'integer' => 42,
                    ]
                ])
            )
            ->shouldBeCalledTimes(1)
        ;
        $provider = new PeclPackageMessagePublisher($exchange->reveal());
        $return = $provider->publish(
            new Message('body', [
                'headers' => [
                    'header' => 'value',
                    'integer' => 42,
                    'array' => ['foo', 'bar'],
                    'another_array' => ['foo' => ['bar', 'burger']],
                ]
            ])
        );
        
        $this->assertNull($return);
    }

    public function test_publish_with_application_headers()
    {
        $exchange = $this->prophesize('\AMQPExchange');
        $exchange
            ->publish(
                Argument::exact('body'),
                Argument::exact(null),
                Argument::exact(0),
                Argument::exact([
                    'headers' => [
                        'another_header' => 'another_value',
                        'string' => 'foobar',
                        'integer' => 42,
                    ]
                ])
            )
            ->shouldBeCalledTimes(1)
        ;
        $provider = new PeclPackageMessagePublisher($exchange->reveal());
        $return = $provider->publish(
            new Message('body', [
                'application_headers' => [
                    'string' => ['S', 'foobar'],
                    'integer' => ['I', 42],
                    'array' => ['A', ['foo', 'bar']]
                ],
                'headers' => [
                    'another_header' => 'another_value'
                ]
            ])
        );

        $this->assertNull($return);
    }

    public function test_it_should_remove_delivery_mode_property_if_equal_to_zero()
    {
        $exchange = $this->prophesize('\AMQPExchange');
        $exchange
            ->publish(
                Argument::exact('body'),
                Argument::exact(null),
                Argument::exact(0),
                Argument::exact([])
            )
            ->shouldBeCalledTimes(1)
        ;
        $provider = new PeclPackageMessagePublisher($exchange->reveal());
        $return = $provider->publish(
            new Message('body', [
                'delivery_mode' => 0
            ])
        );
    
        $this->assertNull($return);
    }
}
