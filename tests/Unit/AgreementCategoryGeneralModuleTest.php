<?php

namespace Tests\Unit;

use App\Models\AgreementCategory;
use Tests\TestCase;

class AgreementCategoryGeneralModuleTest extends TestCase
{
    public function test_general_agreement_is_available_from_every_module(): void
    {
        $category = new AgreementCategory(['assigned_modules' => [AgreementCategory::GENERAL_MODULE]]);

        $this->assertTrue($category->isGeneral());
        $this->assertTrue($category->assignedToModule('riders'));
        $this->assertTrue($category->assignedToModule('employees'));
        $this->assertTrue($category->assignedToModule(AgreementCategory::GENERAL_MODULE));
    }

    public function test_module_specific_agreement_is_not_general(): void
    {
        $category = new AgreementCategory(['assigned_modules' => ['riders']]);

        $this->assertFalse($category->isGeneral());
        $this->assertTrue($category->assignedToModule('riders'));
        $this->assertFalse($category->assignedToModule('employees'));
        $this->assertFalse($category->assignedToModule(AgreementCategory::GENERAL_MODULE));
    }
}
