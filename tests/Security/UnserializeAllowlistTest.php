<?php

namespace DreamFactory\Core\AMQP\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security: Sub::handleDELETE() must constrain unserialize to a known
 * subscriber class.
 *
 * Previously: unserialize(Arr::get(json_decode($job->payload, true), 'data.command'))
 * with no allowed_classes — any object class would be deserialized,
 * triggering __wakeup/__destruct on attacker-chosen gadget chains if
 * the queue payload was attacker-influenced.
 *
 * After the fix, allowed_classes is restricted to the AMQP Subscribe
 * job class, and the result is verified with instanceof before use.
 */
class UnserializeAllowlistTest extends TestCase
{
    private string $contents;

    protected function setUp(): void
    {
        $sourcePath = __DIR__ . '/../../src/Resources/Sub.php';
        $this->assertFileExists($sourcePath);
        $this->contents = file_get_contents($sourcePath);
    }

    public function testUnserializePassesAllowedClasses(): void
    {
        $this->assertMatchesRegularExpression(
            '/unserialize\s*\(.+?[\'"]allowed_classes[\'"]/s',
            $this->contents,
            'unserialize() must pass allowed_classes option to constrain '
            . 'which classes can be deserialized.'
        );
    }

    public function testAllowedClassesListsSubscribeJob(): void
    {
        $this->assertMatchesRegularExpression(
            '/AMQP[\\\\]+Jobs[\\\\]+Subscribe/',
            $this->contents,
            'allowed_classes must reference the Subscribe job class'
        );
    }

    public function testInstanceofGuardAfterUnserialize(): void
    {
        $this->assertMatchesRegularExpression(
            '/!\s*\$obj\s+instanceof\s+\\\\?[A-Z][A-Za-z0-9_\\\\]*Subscribe\b/',
            $this->contents,
            'After unserialize, the result must be guarded with `instanceof` '
            . 'before its methods are called.'
        );
    }
}
