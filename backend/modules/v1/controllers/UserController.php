<?php

namespace app\modules\v1\controllers;

use app\models\forms\EmailChangeRequestForm;
use app\models\forms\ResetPasswordForm;
use app\models\forms\SignupForm;
use app\models\Notification;
use app\models\SendGridContact;
use app\models\User;
use app\models\AdDraft;
use InvalidArgumentException;
use Yii;
use sizeg\jwt\JwtHttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class UserController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\User';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class
        ];

        $behaviors['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'optional' => [
                'options', 'create', 'update'
            ],
        ];

        return $behaviors;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        switch ($action) {
            case 'view':
            case 'view-by-uid':
            case 'update':
                    // User can only manage themselves
                if (empty($model) || $model->uid != Yii::$app->user->getIdentity()->uid) {
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
    
        unset($actions['delete'], $actions['create'], $actions['index'], $actions['update']);
    
        return $actions;
    }

    public function actionViewByUid($id)
    {
        $model = User::findOne(['uid' => $id]);
        $this->checkAccess($this->action->id, $model);
        return $model;
    }

    public function actionUpdate() {
        if (isset(Yii::$app->request->bodyParams['password'])) {
            try {
                $model = new ResetPasswordForm(Yii::$app->request->bodyParams['password_reset_token'] ?? null, [
                    'user' => Yii::$app->user ? Yii::$app->user->getIdentity() : null,
                    'current_password' => Yii::$app->request->getBodyParam('current_password', null),
                ]);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
    
            if ($model->load(Yii::$app->request->bodyParams, '') && $model->resetPassword()) {
                return [];
            } else {
                throw new BadRequestHttpException($e->getMessage());
            }

        } else if (isset(Yii::$app->request->bodyParams['email_change_token'])) {
            try {
                $user = User::findByUid(Yii::$app->user->getIdentity()->uid);
                if ($user->changeEmail(Yii::$app->request->bodyParams['email_change_token'])) {
                    return $user;
                }

                throw new BadRequestHttpException("Unable to change email", 1);
                
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

        } else if (isset(Yii::$app->request->bodyParams['email_confirmation_token'])) {
            try {
                $user = User::find()
                ->emailConfirmationToken(Yii::$app->request->bodyParams['email_confirmation_token'])
                ->andWhere(['uid' => Yii::$app->request->get('id')])
                ->one();

                if ($user && $user->confirmEmail()) {
                    $jwt_string = $user->generateJwt();

                    if (isset($_ENV['ENABLE_WELCOME_EMAIL']) && $_ENV['ENABLE_WELCOME_EMAIL'] === 'y') {
                        Notification::sendUserWelcomeEmail($user);
                    }

                    $user->createStripeCustomer();
                    // $user->createSubscription($_ENV['FREE_PLAN_ID'], null);
                    
                    try {
                        $sgListId = $_ENV['SENDGRID_LIST_ID_FREE_TRIAL'];
                        Yii::info("Adding user {$user->id} to SendGrid list {$sgListId}");
                        $sg = new SendGridContact($_ENV['SENDGRID_API_KEY']);
                        $sg->addToList([
                            'list_ids' => [$_ENV['SENDGRID_LIST_ID'], $sgListId],
                            'contacts' => [
                                [
                                    'email' => $user->email,
                                    'first_name' => $user->fname,
                                    'last_name' => $user->lname,
                                    'custom_fields' => [
                                        SendGridContact::CF_ADPUZL_USER_ID => $user->id,
                                    ],
                                ]
                            ]
                        ]);
                    } catch (\Exception $ex) {
                        echo 'Caught exception: '.  $ex->getMessage();
                    }
                    
                    return array_merge($user->toArray(), ['jwt' => $jwt_string]);
                }

                throw new BadRequestHttpException("Unable to verify user", 1);
                
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        throw new ForbiddenHttpException();

    }
    
    public function actionCreate()
    {
        $model = new SignupForm;
        if ($model->load(Yii::$app->request->bodyParams, '') && $model->signup() !== null) {
            $user = User::findOne(['email' => $model->email]);
            $response = ArrayHelper::toArray($user);
            $totalUserCount = User::find()->count();
            $user->ad_draft_id = Yii::$app->request->getBodyParam('ad_draft_id');
            Notification::sendNewUserCreated($user, $totalUserCount);
            if ($user->ad_draft_id) {
                $adDraft = AdDraft::findOne(['uid' => $user->ad_draft_id]);
                if ($adDraft) {
                    $adDraft->created_by = $user->uid;
                    $adDraft->save();
                }
            }
            return $response;
        } else {
            throw new BadRequestHttpException($model->getErrorSummary(false)[0]);
        }
   }
    
}
