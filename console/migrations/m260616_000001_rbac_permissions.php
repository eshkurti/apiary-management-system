<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Granular RBAC permissions for Honigmanufaktur Lindenhof.
 *
 * Roles (already seeded in m260615_000001_rbac_seed):
 *   administrator, headBeekeeper, fieldBeekeeper, customer
 *
 * This migration adds granular permissions and assigns them to roles.
 * Follows the principle that controllers check permissions, not roles directly.
 */
class m260616_000001_rbac_permissions extends Migration
{
    public function safeUp(): void
    {
        $auth = Yii::$app->authManager;

        // ── Fetch existing roles ──────────────────────────────────────────
        $administrator  = $auth->getRole('administrator');
        $headBeekeeper  = $auth->getRole('headBeekeeper');
        $fieldBeekeeper = $auth->getRole('fieldBeekeeper');
        $customer       = $auth->getRole('customer');

        // ── Define permissions ────────────────────────────────────────────

        // Company profile
        $manageCompanyProfile = $auth->createPermission('manageCompanyProfile');
        $manageCompanyProfile->description = 'View and edit the company profile';
        $auth->add($manageCompanyProfile);

        // Apiary Stand
        $manageApiaryStands = $auth->createPermission('manageApiaryStands');
        $manageApiaryStands->description = 'Register and edit apiary stands';
        $auth->add($manageApiaryStands);

        // Colony
        $manageColonies = $auth->createPermission('manageColonies');
        $manageColonies->description = 'Register and edit colonies';
        $auth->add($manageColonies);

        $viewColonies = $auth->createPermission('viewColonies');
        $viewColonies->description = 'View colonies and Stockkarte';
        $auth->add($viewColonies);

        $moveColony = $auth->createPermission('moveColony');
        $moveColony->description = 'Record a colony movement between stands';
        $auth->add($moveColony);

        // Inspection
        $logInspection = $auth->createPermission('logInspection');
        $logInspection->description = 'Log a colony inspection';
        $auth->add($logInspection);

        // Treatment
        $recordTreatment = $auth->createPermission('recordTreatment');
        $recordTreatment->description = 'Record a veterinary treatment';
        $auth->add($recordTreatment);

        // Harvest and Batch
        $recordHarvest = $auth->createPermission('recordHarvest');
        $recordHarvest->description = 'Record a harvest and create a batch';
        $auth->add($recordHarvest);

        $completeBatchDetails = $auth->createPermission('completeBatchDetails');
        $completeBatchDetails->description = 'Complete batch production and label details';
        $auth->add($completeBatchDetails);

        $evaluateReleaseGate = $auth->createPermission('evaluateReleaseGate');
        $evaluateReleaseGate->description = 'View the five-check release gate for a batch';
        $auth->add($evaluateReleaseGate);

        $releaseBatch = $auth->createPermission('releaseBatch');
        $releaseBatch->description = 'Release a batch for sale';
        $auth->add($releaseBatch);

        // Disease flag
        $manageDiseaseFlag = $auth->createPermission('manageDiseaseFlag');
        $manageDiseaseFlag->description = 'Set and clear colony disease flags';
        $auth->add($manageDiseaseFlag);

        // Compliance exports
        $exportBestandsbuch = $auth->createPermission('exportBestandsbuch');
        $exportBestandsbuch->description = 'Export the Bestandsbuch for a stand';
        $auth->add($exportBestandsbuch);

        $exportStockkarte = $auth->createPermission('exportStockkarte');
        $exportStockkarte->description = 'Export the Stockkarte for a colony';
        $auth->add($exportStockkarte);

        $recallTrace = $auth->createPermission('recallTrace');
        $recallTrace->description = 'Run recall trace search by colony or batch';
        $auth->add($recallTrace);

        // Products
        $manageProducts = $auth->createPermission('manageProducts');
        $manageProducts->description = 'Create, edit, and publish products';
        $auth->add($manageProducts);

        // Orders
        $manageOrders = $auth->createPermission('manageOrders');
        $manageOrders->description = 'View and process orders through fulfilment';
        $auth->add($manageOrders);

        $viewOwnOrders = $auth->createPermission('viewOwnOrders');
        $viewOwnOrders->description = 'View own order history and status';
        $auth->add($viewOwnOrders);

        $placeOrder = $auth->createPermission('placeOrder');
        $placeOrder->description = 'Place an order in the shop';
        $auth->add($placeOrder);

        // Customers
        $manageCustomers = $auth->createPermission('manageCustomers');
        $manageCustomers->description = 'Manage customer and company records';
        $auth->add($manageCustomers);

        // Dashboard
        $viewDashboard = $auth->createPermission('viewDashboard');
        $viewDashboard->description = 'View the operations dashboard';
        $auth->add($viewDashboard);

        // User management
        $manageUsers = $auth->createPermission('manageUsers');
        $manageUsers->description = 'Create and manage user accounts and role assignments';
        $auth->add($manageUsers);

        // ── Assign permissions to roles ───────────────────────────────────

        // administrator — full system access
        $auth->addChild($administrator, $manageCompanyProfile);
        $auth->addChild($administrator, $manageApiaryStands);
        $auth->addChild($administrator, $manageColonies);
        $auth->addChild($administrator, $viewColonies);
        $auth->addChild($administrator, $moveColony);
        $auth->addChild($administrator, $logInspection);
        $auth->addChild($administrator, $recordTreatment);
        $auth->addChild($administrator, $recordHarvest);
        $auth->addChild($administrator, $completeBatchDetails);
        $auth->addChild($administrator, $evaluateReleaseGate);
        $auth->addChild($administrator, $releaseBatch);
        $auth->addChild($administrator, $manageDiseaseFlag);
        $auth->addChild($administrator, $exportBestandsbuch);
        $auth->addChild($administrator, $exportStockkarte);
        $auth->addChild($administrator, $recallTrace);
        $auth->addChild($administrator, $manageProducts);
        $auth->addChild($administrator, $manageOrders);
        $auth->addChild($administrator, $manageCustomers);
        $auth->addChild($administrator, $viewDashboard);
        $auth->addChild($administrator, $manageUsers);

        // headBeekeeper — production, compliance, ecommerce management
        $auth->addChild($headBeekeeper, $viewColonies);
        $auth->addChild($headBeekeeper, $completeBatchDetails);
        $auth->addChild($headBeekeeper, $evaluateReleaseGate);
        $auth->addChild($headBeekeeper, $releaseBatch);
        $auth->addChild($headBeekeeper, $manageDiseaseFlag);
        $auth->addChild($headBeekeeper, $exportBestandsbuch);
        $auth->addChild($headBeekeeper, $exportStockkarte);
        $auth->addChild($headBeekeeper, $recallTrace);
        $auth->addChild($headBeekeeper, $manageProducts);
        $auth->addChild($headBeekeeper, $manageOrders);
        $auth->addChild($headBeekeeper, $viewDashboard);

        // fieldBeekeeper — field operations only
        $auth->addChild($fieldBeekeeper, $viewColonies);
        $auth->addChild($fieldBeekeeper, $logInspection);
        $auth->addChild($fieldBeekeeper, $recordTreatment);
        $auth->addChild($fieldBeekeeper, $recordHarvest);

        // customer — shop only
        $auth->addChild($customer, $placeOrder);
        $auth->addChild($customer, $viewOwnOrders);
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->authManager;

        $permissions = [
            'manageCompanyProfile', 'manageApiaryStands', 'manageColonies',
            'viewColonies', 'moveColony', 'logInspection', 'recordTreatment',
            'recordHarvest', 'completeBatchDetails', 'evaluateReleaseGate',
            'releaseBatch', 'manageDiseaseFlag', 'exportBestandsbuch',
            'exportStockkarte', 'recallTrace', 'manageProducts', 'manageOrders',
            'viewOwnOrders', 'placeOrder', 'manageCustomers', 'viewDashboard',
            'manageUsers',
        ];

        foreach ($permissions as $name) {
            $permission = $auth->getPermission($name);
            if ($permission) {
                $auth->remove($permission);
            }
        }
    }
}
