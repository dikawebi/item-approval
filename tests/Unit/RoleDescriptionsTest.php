<?php

namespace Tests\Unit;

use App\Support\Roles;
use Tests\TestCase;

class RoleDescriptionsTest extends TestCase
{
    public function test_known_roles_have_descriptions(): void
    {
        $this->assertStringContainsStringIgnoringCase('classify', Roles::description('accounting'));
        $this->assertStringContainsStringIgnoringCase('reject', Roles::description('accounting'));
        $this->assertStringContainsStringIgnoringCase('d365', Roles::description('commercial'));
    }

    public function test_unknown_role_falls_back_to_generic_description(): void
    {
        $description = Roles::description('auditor');

        $this->assertNotEmpty($description);
        $this->assertStringContainsStringIgnoringCase('requester', $description);
    }
}
