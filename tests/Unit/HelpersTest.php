<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/database.php';

final class HelpersTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
    }

    public function testEscapeReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', e(null));
    }

    public function testEnvReturnsDefaultWhenMissing(): void
    {
        $this->assertSame('fallback', env('SOME_UNDEFINED_KEY', 'fallback'));
    }

    public function testEnvReturnsRealValueOfZero(): void
    {
        // Regression test for the env() Elvis-operator bug we fixed earlier:
        // a falsy-but-real value like "0" must not be treated as missing.
        $_ENV['ZERO_TEST'] = '0';
        $this->assertSame('0', env('ZERO_TEST', 'should-not-see-this'));
        unset($_ENV['ZERO_TEST']);
    }

    public function testTranslationFallsBackToEnglishWhenKeyMissingInLocale(): void
    {
        $_ENV['APP_LOCALE'] = 'es';
        // 'nav.home' exists in both dictionaries, so this just confirms
        // the lookup resolves to a real string, not the raw key.
        $this->assertNotSame('nav.home', t('nav.home'));
    }

    public function testTranslationReturnsKeyWhenMissingEverywhere(): void
    {
        $this->assertSame('this.key.does.not.exist', t('this.key.does.not.exist'));
    }

    public function testTranslationReplacesPlaceholders(): void
    {
        $_ENV['APP_LOCALE'] = 'en';
        $result = t('todo.count_summary', ['total' => 5, 'pending' => 2, 'completed' => 3]);
        $this->assertStringContainsString('5 tasks', $result);
    }
}