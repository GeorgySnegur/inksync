<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/check_login.php';

/**
 * validate_password() combines a format check (regex) with a common-password
 * blocklist read from backend/security/10k_common_passwords.txt. No DB or
 * session involved -- the wordlist read is the only I/O, and it's
 * deterministic, so this is still a clean unit test.
 */
final class ValidatePasswordTest extends TestCase
{
    public function testAcceptsAStrongPasswordButRejectsWeakOrCommonOnes(): void
    {
        // Long enough, allowed charset, not on the common-password list.
        $this->assertTrue(validate_password('Zx9!qLmP2vRt'));

        // Too short -- fails the {8,64} length requirement in the regex.
        $this->assertFalse(validate_password('Ab1!'));

        // Right length/charset, but "password1" is a textbook common
        // password -- the blocklist must still catch it.
        $this->assertFalse(validate_password('password1'));

        // Disallowed character (€ isn't in [A-Za-z\d@$!%*?&]).
        $this->assertFalse(validate_password('validpass€word1'));
    }
}
