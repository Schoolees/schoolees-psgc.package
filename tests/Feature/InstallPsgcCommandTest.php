<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Console\InstallPsgcCommand;
use Schoolees\Psgc\Tests\TestCase;

class InstallPsgcCommandTest extends TestCase
{
    public function test_install_command_is_non_destructive_by_default(): void
    {
        $command = new class extends InstallPsgcCommand
        {
            public array $calls = [];
            public array $messages = [];
            private array $options = ['seed' => false, 'force' => false];

            public function setOptions(array $options): void
            {
                $this->options = array_replace($this->options, $options);
            }

            public function call($command, array $arguments = []): int
            {
                $this->calls[] = ['command' => $command, 'arguments' => $arguments];

                return self::SUCCESS;
            }

            public function option($key = null)
            {
                return $this->options[$key] ?? null;
            }

            public function info($string, $verbosity = null): void
            {
                $this->messages[] = $string;
            }
        };

        $command->setLaravel($this->app);
        $command->handle();

        $publishCalls = array_values(array_filter(
            $command->calls,
            fn (array $call): bool => $call['command'] === 'vendor:publish'
        ));

        $this->assertCount(3, $publishCalls);
        $this->assertSame(false, $publishCalls[0]['arguments']['--force']);
        $this->assertSame(false, $publishCalls[1]['arguments']['--force']);
        $this->assertSame(false, $publishCalls[2]['arguments']['--force']);
        $this->assertSame('migrate', $command->calls[3]['command']);
    }

    public function test_install_command_can_force_publish_and_seed(): void
    {
        $command = new class extends InstallPsgcCommand
        {
            public array $calls = [];
            private array $options = ['seed' => false, 'force' => false];

            public function setOptions(array $options): void
            {
                $this->options = array_replace($this->options, $options);
            }

            public function call($command, array $arguments = []): int
            {
                $this->calls[] = ['command' => $command, 'arguments' => $arguments];

                return self::SUCCESS;
            }

            public function option($key = null)
            {
                return $this->options[$key] ?? null;
            }

            public function info($string, $verbosity = null): void
            {
            }
        };

        $command->setLaravel($this->app);
        $command->setOptions(['force' => true, 'seed' => true]);
        $command->handle();

        $publishCalls = array_values(array_filter(
            $command->calls,
            fn (array $call): bool => $call['command'] === 'vendor:publish'
        ));

        $this->assertCount(3, $publishCalls);
        $this->assertSame(true, $publishCalls[0]['arguments']['--force']);
        $this->assertSame(true, $publishCalls[1]['arguments']['--force']);
        $this->assertSame(true, $publishCalls[2]['arguments']['--force']);
        $this->assertSame('db:seed', $command->calls[4]['command']);
        $this->assertSame(\Database\Seeders\PSGCSeeder::class, $command->calls[4]['arguments']['--class']);
    }
}
