<?php

declare(strict_types=1);

use yii\db\Migration;

class m260615_000001_rbac_seed extends Migration
{
    public function safeUp(): void
    {
        $auth = Yii::$app->authManager;

        $administrator = $auth->createRole('administrator');
        $administrator->description = 'Full system access';
        $auth->add($administrator);

        $headBeekeeper = $auth->createRole('headBeekeeper');
        $headBeekeeper->description = 'Approves batch release, manages compliance';
        $auth->add($headBeekeeper);

        $fieldBeekeeper = $auth->createRole('fieldBeekeeper');
        $fieldBeekeeper->description = 'Logs inspections, treatments, harvests';
        $auth->add($fieldBeekeeper);

        $customer = $auth->createRole('customer');
        $customer->description = 'Registered buyer';
        $auth->add($customer);

        // Assign administrator role to eshkurti (user id 1)
        $auth->assign($administrator, 1);
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->authManager;

        $auth->revokeAll(1);
        $auth->remove($auth->getRole('administrator'));
        $auth->remove($auth->getRole('headBeekeeper'));
        $auth->remove($auth->getRole('fieldBeekeeper'));
        $auth->remove($auth->getRole('customer'));
    }
}
