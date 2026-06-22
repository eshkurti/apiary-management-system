<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'About Us';
$this->params['breadcrumbs'][] = $this->title;
$this->params['meta_description'] = 'Honigmanufaktur Lindenhof – a family-run apiary in Hof, Bavaria, managing around 120 colonies across six stands in the Frankenwald region.';
$this->params['meta_keywords'] = 'honigmanufaktur lindenhof, beekeeping, honey, frankenwald, hof, bavaria, migratory beekeeping';
?>
<div class="site-about d-flex justify-content-center py-5">
    <div class="card border-0 shadow-sm" style="max-width: 720px; width: 100%;">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

            <p class="text-body-secondary">
                Honigmanufaktur Lindenhof is a family-run honey producer based in Hof, Bavaria.
                We manage around 120 colonies distributed across six apiary stands in Landkreis Hof.
                Our close connection to the local landscape produces honey that reflects the
                natural diversity of the area around Hof, jar by jar.
            </p>

            <p class="text-body-secondary">
                We practise seasonal migratory beekeeping: our colonies move to rapeseed fields
                in spring, mixed wildflower and forest stands in summer, and heather moorland in
                late summer. This gives us a range of distinct varietal honeys throughout the
                year, sold through our online shop to retail customers and local wholesale accounts.
            </p>

            <p class="text-body-secondary">
                We believe honey you can trust starts long before the jar is filled. Every
                treatment, every harvest, and every batch is documented — so any jar on our
                shelf can be traced back to the colony it came from. That transparency is
                something we are genuinely proud of.
            </p>

            <p class="text-body-secondary">
                The team behind Honigmanufaktur Lindenhof numbers around 15 people across field
                beekeeping, production, and logistics. Together we ensure that the work of our
                bees becomes a reliable, high-quality product.
            </p>

            <div class="mt-4">
                <?= Html::a(
                    'Visit the Shop',
                    Url::to(['shop/index']),
                    ['class' => 'btn btn-outline-primary btn-lg'],
                ) ?>
            </div>
        </div>
    </div>
</div>
