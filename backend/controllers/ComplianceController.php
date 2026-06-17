<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\ApiaryStand;
use common\models\Batch;
use common\models\Colony;
use common\models\CompanyProfile;
use common\models\OrderItem;
use common\models\Treatment;
use common\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Compliance module (US-CO-01 .. US-CO-06).
 *
 * Reads Production Management records to: evaluate the batch release gate,
 * release cleared batches, export the Bestandsbuch and Stockkarte, and run
 * recall traces. No new compliance data is entered in this module — all gate
 * logic lives on the Batch model and is simply called and rendered here.
 *
 * Permission map:
 *   release-gate / gate → evaluateReleaseGate
 *   release             → releaseBatch
 *   bestandsbuch        → exportBestandsbuch
 *   stockkarte          → exportStockkarte
 *   recall              → recallTrace
 */
class ComplianceController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['release-gate', 'gate'],
                        'roles' => ['evaluateReleaseGate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['release'],
                        'roles' => ['releaseBatch'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['bestandsbuch'],
                        'roles' => ['exportBestandsbuch'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['stockkarte'],
                        'roles' => ['exportStockkarte'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['recall'],
                        'roles' => ['recallTrace'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['release' => ['post']],
            ],
        ];
    }

    // ── Release gate (US-CO-01, US-CO-02) ─────────────────────────────────

    /**
     * Lists every batch with its current status (US-CO-01).
     */
    public function actionReleaseGate(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Batch::find()->with('apiaryStand')->orderBy(['harvest_date' => SORT_DESC, 'id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('release-gate', ['dataProvider' => $dataProvider]);
    }

    /**
     * Shows the five-check release gate for a single batch (AC-CO-01.1..01.4).
     * The checks are evaluated live by the model on each request (AC-CO-01.3).
     */
    public function actionGate(int $id): string
    {
        return $this->render('gate', ['model' => $this->findBatch($id)]);
    }

    /**
     * Releases a batch if all five gate conditions pass (US-CO-02).
     * Identity and timestamp are recorded by Batch::release() (AC-CO-02.2).
     */
    public function actionRelease(int $id): Response
    {
        $model = $this->findBatch($id);

        if ($model->release()) {
            Yii::$app->session->setFlash('success', "Batch {$model->lot_number} released for sale.");
        } else {
            Yii::$app->session->setFlash('error', 'Batch cannot be released — one or more gate conditions are failing.');
        }

        return $this->redirect(['gate', 'id' => $id]);
    }

    // ── Bestandsbuch export (US-CO-03) ────────────────────────────────────

    /**
     * Bestandsbuch export form and CSV generation (US-CO-03).
     * Filtered by apiary stand and date range; produces a UTF-8 CSV with BOM
     * matching EU Reg. 2019/6 Article 108(2) columns (AC-CO-03.2..03.4).
     */
    public function actionBestandsbuch(): string|Response
    {
        $standId   = Yii::$app->request->get('stand_id');
        $dateFrom  = (string) Yii::$app->request->get('date_from', '');
        $dateTo    = (string) Yii::$app->request->get('date_to', '');
        $submitted = Yii::$app->request->get('export') !== null;

        if ($submitted && $standId !== null && $standId !== '') {
            return $this->exportBestandsbuch((int) $standId, $dateFrom, $dateTo);
        }

        return $this->render('bestandsbuch', [
            'standId'  => $standId,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    private function exportBestandsbuch(int $standId, string $dateFrom, string $dateTo): Response
    {
        $stand = ApiaryStand::findOne($standId);
        if ($stand === null) {
            throw new NotFoundHttpException('The selected apiary stand does not exist.');
        }

        $company = CompanyProfile::getInstance();

        $query = Treatment::find()
            ->where(['apiary_stand_id' => $standId])
            ->orderBy(['application_date' => SORT_ASC, 'id' => SORT_ASC]);
        if ($dateFrom !== '') {
            $query->andWhere(['>=', 'application_date', $dateFrom]);
        }
        if ($dateTo !== '') {
            $query->andWhere(['<=', 'application_date', $dateTo]);
        }
        /** @var Treatment[] $treatments */
        $treatments = $query->with('colony')->all();

        // Document header carrying company identity and stand authority number (AC-CO-03.3)
        $rows = [
            ['Bestandsbuch — Treatment Ledger (EU Reg. 2019/6 Art. 108(2))'],
            ['Company', $company->company_name],
            ['Tierhalter (Keeper)', $company->keeper_name],
            ['Address', trim($company->address . ', ' . $company->postcode . ' ' . $company->city, ', ')],
            ['Apiary Stand', $stand->stand_code . ' — ' . $stand->name],
            ['Veterinäramt Reg. No.', $stand->authority_reg_number],
            ['Date Range', ($dateFrom ?: 'earliest') . ' to ' . ($dateTo ?: 'latest')],
            [],
            // Column header row (AC-CO-03.2)
            [
                'Application Date',
                'Medicinal Product',
                'Pharmaceutical Batch/Charge No.',
                'Treatment Type',
                'Quantity per Colony',
                'Supplier Name',
                'Supplier Address',
                'Stand Location',
                'Colony Identifier',
                'Colonies Treated at Stand',
                'Withdrawal Period (days)',
                'Wartezeit Expiry',
                'Treatment Duration (days)',
                'Operator',
            ],
        ];

        foreach ($treatments as $t) {
            $rows[] = [
                $t->application_date,
                $t->product_name,
                $t->pharmaceutical_batch_number,
                Treatment::typeLabels()[$t->treatment_type] ?? $t->treatment_type,
                $t->quantity_per_colony,
                $t->supplier_name,
                $t->supplier_address,
                $stand->stand_code,
                $t->colony->colony_code ?? '',
                $t->colonies_treated_at_stand,
                $t->withdrawal_days,
                $t->wartezeit_expiry,
                $t->treatment_duration_days,
                $t->operator_name,
            ];
        }

        $filename = sprintf('bestandsbuch_%s_%s.csv', $stand->stand_code, date('Ymd'));
        return $this->sendCsv($filename, $rows);
    }

    // ── Stockkarte export (US-CO-04) ──────────────────────────────────────

    /**
     * Stockkarte export form and CSV generation (US-CO-04).
     * All inspections, treatments and harvests for the colony, in chronological
     * order, with full field values and submitting user identity (AC-CO-04.1..04.4).
     */
    public function actionStockkarte(): string|Response
    {
        $colonyId  = Yii::$app->request->get('colony_id');
        $submitted = Yii::$app->request->get('export') !== null;

        if ($submitted && $colonyId !== null && $colonyId !== '') {
            return $this->exportStockkarte((int) $colonyId);
        }

        return $this->render('stockkarte', ['colonyId' => $colonyId]);
    }

    private function exportStockkarte(int $colonyId): Response
    {
        $colony = Colony::findOne($colonyId);
        if ($colony === null) {
            throw new NotFoundHttpException('The selected colony does not exist.');
        }

        // getStockkarte() returns entries newest-first; export in chronological order.
        $entries = $colony->getStockkarte();
        usort($entries, static fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));

        // Header carrying colony identity (AC-CO-04.3)
        $rows = [
            ['Stockkarte — Colony Record'],
            ['Colony', $colony->colony_code],
            ['Current Apiary Stand', $colony->apiaryStand->stand_code ?? ''],
            ['Queen Year', $colony->queen_year],
            ['Status', ucfirst($colony->status)],
            [],
            ['Date', 'Record Type', 'Details', 'Submitted By'],
        ];

        foreach ($entries as $entry) {
            $record = $entry['record'];
            $rows[] = [
                $entry['date'],
                ucfirst($entry['type']),
                $this->describeStockkarteEntry($entry['type'], $record),
                $this->resolveUsername($record->created_by ?? null),
            ];
        }

        $filename = sprintf('stockkarte_%s_%s.csv', $colony->colony_code, date('Ymd'));
        return $this->sendCsv($filename, $rows);
    }

    /**
     * Flattens a Stockkarte record's fields into a single readable detail cell.
     */
    private function describeStockkarteEntry(string $type, object $record): string
    {
        $pairs = match ($type) {
            'inspection' => [
                'Weather'        => $record->weather,
                'Brood score'    => $record->brood_pattern_score,
                'Queen sighted'  => $record->queen_sighted ? 'yes' : 'no',
                'Disease'        => $record->disease_indicators,
                'Notes'          => $record->notes,
            ],
            'treatment' => [
                'Type'            => Treatment::typeLabels()[$record->treatment_type] ?? $record->treatment_type,
                'Product'         => $record->product_name,
                'Batch/Charge No' => $record->pharmaceutical_batch_number,
                'Quantity'        => $record->quantity_per_colony,
                'Supplier'        => $record->supplier_name,
                'Withdrawal days' => $record->withdrawal_days,
                'Wartezeit until' => $record->wartezeit_expiry,
                'Operator'        => $record->operator_name,
            ],
            'harvest' => [
                'Lot number'   => $record->lot_number,
                'Honey variety'=> $record->honey_variety,
                'Quantity kg'  => $record->harvest_quantity_kg,
                'Status'       => $record->status,
            ],
            default => [],
        };

        $parts = [];
        foreach ($pairs as $label => $value) {
            if ($value !== null && $value !== '') {
                $parts[] = $label . ': ' . $value;
            }
        }
        return implode('; ', $parts);
    }

    // ── Recall trace (US-CO-06) ───────────────────────────────────────────

    /**
     * Recall trace search by colony code or batch lot number (US-CO-06).
     * Traverses colony → batch → order_item → order → customer across all
     * fulfilment stages including delivered (AC-CO-06.1..06.4). Read-only.
     */
    public function actionRecall(): string
    {
        $searchType = (string) Yii::$app->request->get('search_type', 'lot');
        $term       = trim((string) Yii::$app->request->get('term', ''));

        $batches = [];   // batches in scope of the trace
        $orders  = [];   // affected order rows
        $searched = false;
        $notFound = false;

        if ($term !== '') {
            $searched = true;

            if ($searchType === 'colony') {
                $colony = Colony::find()->where(['colony_code' => $term])->one();
                if ($colony === null) {
                    $notFound = true;
                } else {
                    $batches = $colony->batches; // every batch sourced from this colony (AC-CO-06.1)
                }
            } else {
                $batch = Batch::find()->where(['lot_number' => $term])->one();
                if ($batch === null) {
                    $notFound = true;
                } else {
                    $batches = [$batch];
                }
            }

            $lotNumbers = array_values(array_unique(array_map(
                static fn (Batch $b): string => $b->lot_number,
                $batches,
            )));

            if (!empty($lotNumbers)) {
                $orders = $this->findAffectedOrders($lotNumbers);
            }
        }

        return $this->render('recall', [
            'searchType' => $searchType,
            'term'       => $term,
            'batches'    => $batches,
            'orders'     => $orders,
            'searched'   => $searched,
            'notFound'   => $notFound,
        ]);
    }

    /**
     * Returns affected order lines for the given lot numbers, across all
     * fulfilment stages (AC-CO-06.4). Each row carries order reference,
     * order date, customer name, lot number and quantity (AC-CO-06.3).
     *
     * @param string[] $lotNumbers
     * @return array<int, array{order:string, date:string, customer:string, status:string, lot:string, quantity:int}>
     */
    private function findAffectedOrders(array $lotNumbers): array
    {
        /** @var OrderItem[] $items */
        $items = OrderItem::find()
            ->where(['lot_number' => $lotNumbers])
            ->with(['order', 'order.customer'])
            ->all();

        $rows = [];
        foreach ($items as $item) {
            $order = $item->order;
            if ($order === null) {
                continue;
            }
            $rows[] = [
                'order'    => $order->order_number,
                'date'     => $order->order_date,
                'customer' => $order->customer->name ?? '—',
                'status'   => $order->status,
                'lot'      => $item->lot_number,
                'quantity' => (int) $item->quantity,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($b['date'], $a['date']));
        return $rows;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function resolveUsername(?int $userId): string
    {
        if ($userId === null) {
            return '—';
        }
        static $cache = [];
        if (!array_key_exists($userId, $cache)) {
            $user = User::findOne($userId);
            $cache[$userId] = $user->username ?? ('user #' . $userId);
        }
        return $cache[$userId];
    }

    /**
     * Streams an array of rows as a UTF-8 CSV download with a BOM for Excel
     * compatibility (AC-CO-03.4, AC-CO-04.4).
     *
     * @param array<int, array<int, mixed>> $rows
     */
    private function sendCsv(string $filename, array $rows): Response
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->content = "\xEF\xBB\xBF" . $csv; // UTF-8 byte-order mark
        return $response;
    }

    private function findBatch(int $id): Batch
    {
        $model = Batch::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested batch does not exist.');
        }
        return $model;
    }
}
