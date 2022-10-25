<?php

namespace app\modules\v1\controllers;
use Yii;
use sizeg\jwt\JwtHttpBearerAuth;
use app\models\Ad;
use app\models\S3Signature;
use app\models\User;
use Aws\S3\S3Client;
use Ramsey\Uuid\Uuid;
use yii\web\BadRequestHttpException;

class AdController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\Ad';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // add CORS filter
        $paginationHeaders = ['X-Pagination-Total-Count', 'X-Pagination-Page-Count', 'X-Pagination-Current-Page', 'X-Pagination-Per-Page'];
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => $paginationHeaders,
            ]
        ];

        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];
    
        return $behaviors;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        switch ($action) {
            case 'view':
            case 'update':
            case 'delete':
                $theUser = User::findByUid(Yii::$app->user->identity->uid);
                if ($model->created_by != $theUser->id) {
                    throw new \yii\web\ForbiddenHttpException("You cannot access this.", 1);
                }
                break;
            default:
                # code...
                break;
        }
    }

    public function actions()
    {
        $actions = parent::actions();
    
        // disable the "delete" and "create" actions
        // unset($actions['delete'], $actions['create']);
    
        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
    
        return $actions;
    }

    public function actionLeads($id) {
        $ad = Ad::findOne(['id' => $id]);
        $ad->refreshFromRemote();
        $adArray = $ad->toArray();

        return $ad->getLeads();
    }

    public function actionLeadsCsv($id) {
        $ad = Ad::findOne(['id' => $id]);
        $ad->refreshFromRemote();
        $adArray = $ad->toArray();
        $leads = $ad->getLeads();

        $csvFilename = Uuid::uuid4() . ".csv";
        $s3Key = "tmp/" . $csvFilename;
        $csvFilePath = "/" . $s3Key;
        $fp = fopen($csvFilePath, 'w');

        $headerLead = $leads[0];
        $header = [];
        foreach ($headerLead['field_data'] as $field) {
            $header[] = $field['name'];
        }
        fputcsv($fp, $header);

        foreach ($leads as $lead) {
            foreach ($lead['field_data'] as $field) {
                $values[] = implode(" | ", $field['values']);
            }
            fputcsv($fp, $values);
        }
        fclose($fp);

        $csvStr = file_get_contents($csvFilePath);

        $s3Client = new S3Client(array(
        'credentials' => new \Aws\Credentials\Credentials(
            $_ENV['AWS_CLIENT_ACCESS_KEY'],
            $_ENV['AWS_CLIENT_SECRET_KEY']
            ),
            'region' => 'us-east-1',
            'version' => '2006-03-01'
        ));

        $toBeUploaded = [
            'SourceFile' => $csvFilePath,
            'Key' => $s3Key,
            'Bucket' => $_ENV['S3_BUCKET_NAME'],
        ];

        $s3Client->putObject($toBeUploaded);

        $request = $s3Client->createPresignedRequest($s3Client->getCommand('GetObject', [
            'Bucket' => $_ENV['S3_BUCKET_NAME'],
            'Key' => $s3Key,
            'ResponseContentDisposition' => 'attachment; filename="leads.csv"',
        ]), '+24 hours');

        // Get the actual presigned-url
        $presignedUrl = (string)$request->getUri();

        return [
            'download_url' => $presignedUrl
        ];
    }
    
    public function prepareDataProvider()
    {
        // prepare and return a data provider for the "index" action
        $theUser = User::findByUid(Yii::$app->user->identity->uid);
        $data = $theUser->getAds(); 

        $provider = new \yii\data\ActiveDataProvider([
            'query' => $data->orderBy('id DESC'),
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
            // 'pagination' => [
            //     'pageSize' => 50,
            // ],
        ]);

        return $provider;
    }
}
