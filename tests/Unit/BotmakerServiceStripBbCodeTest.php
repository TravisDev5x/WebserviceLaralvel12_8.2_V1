<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BotmakerService;
use ReflectionMethod;
use Tests\TestCase;

class BotmakerServiceStripBbCodeTest extends TestCase
{
    public function test_strip_bbcode_removes_agent_prefix_with_bbcode_and_br(): void
    {
        $input = '[b]ERIC RAFAEL PEREZ HERNANDEZ:[/b] [br]hola';
        $this->assertSame('hola', $this->strip($input));
    }

    public function test_strip_bbcode_removes_agent_prefix_with_bbcode_no_space_before_br(): void
    {
        $input = '[b]ERIC:[/b][br]mensaje';
        $this->assertSame('mensaje', $this->strip($input));
    }

    public function test_strip_bbcode_removes_agent_prefix_with_bbcode_and_real_newline(): void
    {
        $input = "[b]ERIC RAFAEL:[/b]\nmensaje";
        $this->assertSame('mensaje', $this->strip($input));
    }

    public function test_strip_bbcode_removes_plain_agent_prefix_without_bbcode(): void
    {
        $input = "ERIC RAFAEL PEREZ HERNANDEZ:\nmensaje";
        $this->assertSame('mensaje', $this->strip($input));
    }

    private function strip(string $input): string
    {
        $method = new ReflectionMethod(BotmakerService::class, 'stripBbCode');
        $method->setAccessible(true);

        /** @var string $result */
        $result = $method->invoke(null, $input);

        return $result;
    }
}
