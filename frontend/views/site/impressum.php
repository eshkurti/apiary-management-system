<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Impressum';
$this->params['meta_description'] = 'Impressum der Honigmanufaktur Lindenhof – Angaben gemäß § 5 TMG.';
$this->params['meta_keywords'] = 'impressum, honigmanufaktur lindenhof, angaben gemäß tmg, hof, bayern';
?>
<div class="site-impressum d-flex justify-content-center py-5">
    <div class="card border-0 shadow-sm" style="max-width: 720px; width: 100%;">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

            <h2 class="h6 fw-semibold mt-4 mb-2">Angaben gemäß § 5 TMG</h2>
            <p class="text-body-secondary mb-0">
                Honigmanufaktur Lindenhof<br>
                Lindenstraße 12<br>
                95028 Hof (Saale)<br>
                Bayern, Deutschland
            </p>

            <h2 class="h6 fw-semibold mt-4 mb-2">Vertreten durch</h2>
            <p class="text-body-secondary mb-0">
                Jürgen Heym (Inhaber und Tierhalter)
            </p>

            <h2 class="h6 fw-semibold mt-4 mb-2">Kontakt</h2>
            <p class="text-body-secondary mb-0">
                Telefon: +49 (0)9281 123456<br>
                E-Mail: <?= Html::a(
                    'info@honigmanufaktur-lindenhof.de',
                    'mailto:info@honigmanufaktur-lindenhof.de',
                    ['class' => 'text-decoration-none'],
                ) ?>
            </p>

            <h2 class="h6 fw-semibold mt-4 mb-2">Veterinärbehörde</h2>
            <p class="text-body-secondary mb-0">
                Veterinäramt Landkreis Hof<br>
                Registriernummer Tierhalter: BY-09475-T-001
            </p>

            <h2 class="h6 fw-semibold mt-4 mb-2">Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
            <p class="text-body-secondary mb-0">
                Jürgen Heym, Lindenstraße 12, 95028 Hof (Saale)
            </p>

            <h2 class="h6 fw-semibold mt-4 mb-2">Haftungsausschluss</h2>
            <p class="text-body-secondary mb-0">
                Die Inhalte dieser Website wurden mit größtmöglicher Sorgfalt erstellt. Für die
                Richtigkeit, Vollständigkeit und Aktualität der Inhalte übernehmen wir keine Gewähr.
            </p>
        </div>
    </div>
</div>
