<?php

namespace Schoolees\Psgc\Tests\Feature;

use RuntimeException;
use Schoolees\Psgc\Support\Utility;
use Schoolees\Psgc\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JsonExceptionTest extends TestCase
{
    public function test_server_exceptions_are_generic_when_debug_is_disabled(): void
    {
        config()->set('app.debug', false);

        $response = Utility::jsonException(new RuntimeException('Database password leaked'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            ['code' => 500, 'error' => 'Server Error'],
            $response->getData(true)
        );
    }

    public function test_server_exceptions_keep_their_message_when_debug_is_enabled(): void
    {
        config()->set('app.debug', true);

        $response = Utility::jsonException(new RuntimeException('Database password leaked'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            ['code' => 500, 'error' => 'Database password leaked'],
            $response->getData(true)
        );
    }

    public function test_http_client_errors_keep_their_message(): void
    {
        config()->set('app.debug', false);

        $response = Utility::jsonException(new NotFoundHttpException('Region not found'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(
            ['code' => 404, 'error' => 'Region not found'],
            $response->getData(true)
        );
    }
}
