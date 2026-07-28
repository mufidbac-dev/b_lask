<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\StubReplacer;

class StubReplacerTest extends TestCase
{
    public function test_replace_and_detects_unresolved_placeholder()
    {
        $replacer = new StubReplacer();
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stub_test_' . uniqid() . '.stub';
        file_put_contents($tmp, "Hello {{ name }} and {{ missing }}");

        $this->expectException(\RuntimeException::class);
        $replacer->replace($tmp, ['name' => 'World']);

        @unlink($tmp);
    }

    public function test_write_and_overwrite_behavior()
    {
        $replacer = new StubReplacer();
        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stub_out_' . uniqid() . '.php';
        $replacer->write('content', $dest, true);
        $this->assertFileExists($dest);
        // writing without force should throw
        $this->expectException(\RuntimeException::class);
        $replacer->write('other', $dest, false);
        @unlink($dest);
    }
}
