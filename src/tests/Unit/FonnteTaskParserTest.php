<?php

namespace Tests\Unit;

use App\Http\Controllers\FonnteWebhookController;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FonnteTaskParserTest extends TestCase
{
    public function test_relative_day_keeps_its_period_and_category(): void
    {
        $parser = new class extends FonnteWebhookController {
            public function parse(string $input, string $timezone): array
            {
                return $this->parseTaskInput($input, $timezone);
            }
        };

        Carbon::setTestNow(Carbon::parse('2026-11-11 06:00:00', 'Asia/Makassar'));
        try {
            foreach ([
                ['besok pagi', '2026-11-12 08:00:00'],
                ['besok siang', '2026-11-12 12:00:00'],
                ['besok sore', '2026-11-12 17:00:00'],
                ['besok malam', '2026-11-12 20:00:00'],
                ['lusa pagi', '2026-11-13 08:00:00'],
                ['nanti sore', '2026-11-11 17:00:00'],
                ['pagi ini', '2026-11-11 08:00:00'],
            ] as [$when, $expected]) {
                $result = $parser->parse("{$when} baca buku kategori Pribadi tinggi", 'Asia/Makassar');

                $this->assertSame('Baca buku', $result['title']);
                $this->assertSame('Pribadi', $result['category']);
                $this->assertSame('high', $result['priority']);
                $this->assertSame($expected, $result['deadline']->format('Y-m-d H:i:s'));
                $this->assertSame('Asia/Makassar', $result['deadline']->getTimezone()->getName());
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_pipe_and_natural_inputs_agree_on_local_morning_deadline(): void
    {
        $parser = new class extends FonnteWebhookController {
            public function parse(string $input): array
            {
                return $this->parseTaskInput($input, 'Asia/Makassar');
            }
        };

        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00', 'Asia/Makassar'));
        try {
            $natural = $parser->parse('besok pagi sarapan kategori Pribadi santai');
            $pipe = $parser->parse('Sarapan | besok pagi | Pribadi | santai');

            $this->assertSame('2026-11-12 08:00:00', $natural['deadline']->format('Y-m-d H:i:s'));
            $this->assertEquals($natural, $pipe);
        } finally {
            Carbon::setTestNow();
        }
    }
}
