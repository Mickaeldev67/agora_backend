<?php

namespace App\Tests;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setPseudo('Iphone');
        
        $this->assertSame('Iphone', $user->getPseudo());
    }
}
