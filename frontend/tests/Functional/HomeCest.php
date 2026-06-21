<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use frontend\tests\Support\FunctionalTester;
use Yii;

final class HomeCest
{
    public function checkOpen(FunctionalTester $I): void
    {
        $I->amOnRoute(Yii::$app->homeUrl);
        $I->see('Honigmanufaktur Lindenhof');
        $I->seeLink('Shop');
    }
}
