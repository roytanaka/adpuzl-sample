<?php

namespace app\models;

use Yii;
use app\helpers\Mail;
use app\models\behaviors\TimestampBehavior;
use app\models\User;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use FacebookAds\Api;
use FacebookAds\Exception\Exception as FBException;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\helpers\Json;
use app\models\CampaignActivity;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "ad".
 *
 * @property int $id
 * @property string|null $ad_id
 * @property string|null $campaign_id
 * @property string|null $ad_set_id
 * @property string|null $status
 * @property int|null $created_by
 * @property int $created_at
 * @property int $updated_at
 */
class Ad extends \yii\db\ActiveRecord
{

    const STATUS_CREATED = 'CREATED';
    const STATUS_DELETED = 'DELETED';
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_STOPPED = 'STOPPED';
    const STATUS_NEEDS_ATTENTION = 'NEEDS_ATTENTION';

    const EFFECTIVE_STATUS_ACTIVE = 'ACTIVE';
    const EFFECTIVE_STATUS_PAUSED = 'PAUSED';
    const EFFECTIVE_STATUS_DELETED = 'DELETED';
    const EFFECTIVE_STATUS_PENDING_REVIEW = 'PENDING_REVIEW';
    const EFFECTIVE_STATUS_DISAPPROVED = 'DISAPPROVED';
    const EFFECTIVE_STATUS_PREAPPROVED = 'PREAPPROVED';
    const EFFECTIVE_STATUS_PENDING_BILLING_INFO = 'PENDING_BILLING_INFO';
    const EFFECTIVE_STATUS_CAMPAIGN_PAUSED = 'CAMPAIGN_PAUSED';
    const EFFECTIVE_STATUS_ARCHIVED = 'ARCHIVED';
    const EFFECTIVE_STATUS_ADSET_PAUSED = 'ADSET_PAUSED';
    const EFFECTIVE_STATUS_IN_PROCESS = 'IN_PROCESS';
    const EFFECTIVE_STATUS_WITH_ISSUES = 'WITH_ISSUES';

    const AD_SATUS_CHANGE_DEFINITION = [
        'any' => [
            self::EFFECTIVE_STATUS_ACTIVE => 'ACTIVE', // Ad is approved/scheduled/running
            self::EFFECTIVE_STATUS_DISAPPROVED => 'DISAPPROVED', // Ad rejected
            self::EFFECTIVE_STATUS_PENDING_REVIEW => 'PENDING_REVIEW',
            self::EFFECTIVE_STATUS_PAUSED => 'PAUSED', // Ad Paused
            self::EFFECTIVE_STATUS_CAMPAIGN_PAUSED => 'PAUSED', // Ad Paused
            self::EFFECTIVE_STATUS_ADSET_PAUSED => 'PAUSED', // Ad Paused
        ],
    ];

    public $statusDisplay;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ad';
    }
    
    public static function filterActiveAd($ad)
    {
        return $ad->status == Ad::STATUS_ACTIVE && $ad->effective_status == Ad::EFFECTIVE_STATUS_ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_by', 'start_time', 'end_time', 'page_id', 'last_update_timestamp', 'lead_count'], 'integer'],
            [['ad_id'], 'required'],
            [['ad_budget', 'business_id', 'ad_account_id', 'creative_data', 'creative', 'adset'], 'safe'],
            [['ad_id', 'ad_name', 'objective', 'campaign_id', 'ad_set_id', 'status', 'effective_status'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_by'
                ],
                'value' => function ($event) {
                    return Yii::$app->user->id;
                },
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ad_id' => 'Ad ID',
            'campaign_id' => 'Campaign ID',
            'ad_set_id' => 'Ad Set ID',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['creative'] = 'creativeData';
        $fields['adset'] = 'adsetData';
        return $fields;
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            $this->creative = json_encode($this->creative);
            $this->adset = json_encode($this->adset);
            $theUser = User::findByUid(Yii::$app->user->identity->uid);
            $this->created_by = $theUser->id;
        } else if (is_array($this->creative)) {
            $this->creative = json_encode($this->creative);
        }
        return true;
    }
    
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getStatusDisplay() {
        switch ($this->status) {
            case self::STATUS_CREATED:
                return 'Ad Submitted';
            
            case self::STATUS_ACTIVE:
                return 'Ad is running';
    
            default:
                # code...
                break;
        }
    }

    static function isExpired($end_time) {
        $end_time = '2017-08-20T23:06:00-0400';

    }

    public function sendAdStoppedNotificationEmail() {

    }

    public function sendAdStatusChangeNotificationEmail($oldStatus, $newStatus) {
        if ($oldStatus == $newStatus) {
            return true;
        }
        // Ad has stopped?
        // ? Definition of stopped: $newStatus == EFFECTIVE_STATUS_ACTIVE and ad set `stop_time` is greater than now

        $ad_status = self::AD_SATUS_CHANGE_DEFINITION['any'][$newStatus] ?? null;
        if ($ad_status == 'PAUSED') {
            // Ad is stopped
            $theUser = User::findOne(['id' => $this->created_by]);
            if (Mail::toUser($theUser, 'ad-stopped', [
                'ad_title' => $this->adTitle,
                'create_new_ad_url' => '',
                'my_ads_page_url' => '',
            ])) {
                Yii::info('Mail send success ' . $newStatus);
            } else {
                Yii::info('Mail send fail');
            }

        } else if ($ad_status == 'ACTIVE') {
            // Ad is live
            $theUser = User::findOne(['id' => $this->created_by]);
            if (Mail::toUser($theUser, 'ad-approved', [
                'ad_title' => $this->adTitle,
            ])) {
                Yii::info('Mail send success ' . $newStatus);
            } else {
                Yii::info('Mail send fail');
            }

        } else if ($newStatus == self::EFFECTIVE_STATUS_DISAPPROVED) {
            // Ad is disapproved
            $theUser = User::findOne(['id' => $this->created_by]);
            if (Mail::toUser($theUser, 'ad-disapproved', [
                'ad_title' => $this->adTitle,
                'ad_manager_url' => $this->adManagerUrl,
            ])) {
                Yii::info('Mail send success ' . $newStatus);
            } else {
                Yii::info('Mail send fail');
            }

        } else {
            $theUser = User::findOne(['id' => $this->created_by]);
            if (Mail::toUser($theUser, 'ad-status-change-generic', [
                'ad_title' => $this->adTitle,
                'ad_manager_url' => $this->adManagerUrl,
                'new_status' => $newStatus,
            ])) {
                Yii::info('Mail send success ' . $newStatus);
            } else {
                Yii::info('Mail send fail');
            }
            // Yii::info("Status changed {$oldStatus} => {$newStatus}, no matching rule.");
        }
    }

    public function getApiClient() {
        $api = Api::init(
            $_ENV['FB_APP_ID'],
            $_ENV['FB_APP_SECRET'],
            $this->user->access_token, // Your user access token,
            false,
          );
        $api->setDefaultGraphVersion('13.0');
        return $api;
    }

    public function refreshFromRemote() {
        $this->last_update_timestamp = time();
        $this->save();
    
    
        // Call Facebook API to retrieve ad status
        $api = $this->getApiClient();
        try {
          // Returns a `Facebook\FacebookResponse` object
          $response = $api->call(
            "/{$this->ad_id}", 'GET', ['fields' => 'name,id,ad_review_feedback,account_id,effective_status,status,creative{name, image_url, object_story_spec},adset{targeting,start_time,end_time}']
          );

    
        } catch(FBException $e) {
          Yii::error('Graph returned an error: ' . $e->getMessage());
          var_dump($e->getMessage());
          return;
        } catch(FBException $e) {
          Yii::error('Facebook SDK returned an error: ' . $e->getMessage());
          var_dump($e->getMessage());
          return;
        }
    
        $facebook_ad = $response->getContent();
        $oldAdStatus = $this->status;
        $this->effective_status = strtoupper($facebook_ad['effective_status']);
        $this->status = strtoupper($facebook_ad['status']);
        $this->creative = json_encode($facebook_ad['creative']);
        $this->save();
        
        // Try to retrieve lead count
        $lead_form_id = null;
        $lead_count = null;
        try {
            $lead_form_id = $facebook_ad['creative']['object_story_spec']['link_data']['call_to_action']['value']['lead_gen_form_id'];
            Yii::info('Is lead ad, getting leads');
            $this->lead_count = count($this->getLeads());

        } catch (\Throwable $th) {
            Yii::info('Not a lead ad');
        }
    
        if ($this->save()) {
          Yii::info('Retrieve ad status success');
          Yii::info("ad id {$this->id}: {$this->status}");
    
          Yii::info('Retrieving Ad Insights');
          $response = $api->call(
            "/{$this->ad_id}/insights", 'GET', [
              'fields' => 'unique_clicks,impressions,unique_inline_link_click_ctr',
              'date_preset' => 'maximum',
            ]
          );
          $response = $response->getContent();
          $this->insights = json_encode($response['data']);
          $this->save();
    
        } else {
          Yii::info('Retrieve ad status fail');
          Yii::info("ad id {$this->id}: {$this->status}");
        }
        return $facebook_ad;
    }

    public function retrieveCampaignInsights($insightsDates = []) {

        // if ($this->effective_status != 'ACTIVE') {
        //     echo "Ad {$this->id} is not active, no need to retrieve campaign insights\n";
        //     return;
        // }

        if (empty($insightsDates)) {
            $insightsDates = [date('Y-m-d')];
        }

        $timeRanges = [];
        foreach ($insightsDates as $idate) {
            $timeRanges[] = [
                'since' => $idate,
                'until' => $idate,
            ];
        }
        $endDate = $insightsDates[0];
        $startDate = $insightsDates[count($insightsDates) - 1];
        echo "Retrieving campaign insights for {$this->campaign_id}, between {$startDate} and {$endDate}\n";

        $this->last_update_timestamp = time();
        $this->save();

        $api = $this->getApiClient();

        // First day of a specific month
        // $d = new \DateTime($insightsDate);
        // $d->modify('first day of this month');
        // $firstDayOfMonth = substr($d->format(DATE_ATOM), 0, 10);
        // $dateRanges = [
        //     [
        //         'since' => $insightsDate,
        //         'until' => $insightsDate
        //     ],
        //     [
        //         'since' => $firstDayOfMonth,
        //         'until' => $insightsDate
        //     ]
        // ];

        

        try {
            $response = $api->call(
                "/{$this->campaign_id}/insights", 'GET', [
                    'fields' => 'unique_clicks,impressions,unique_inline_link_click_ctr,spend,account_currency',
                    'time_ranges' => $timeRanges,
                ]
            );
        } catch (\Throwable $th) {
            echo $th->getMessage() . "\n";
            return;
        }

        $response = $response->getContent();

        if (empty($response['data'])) {
            echo "Campaign {$this->campaign_id} has no insights for the specified dates\n";
            return;
        }
        echo "Campaign {$this->campaign_id} found insights!\n";

        foreach ($response['data'] as $insights) {
            $theCampaignActivity = CampaignActivity::findOne([
                'user_id' => $this->created_by,
                'campaign_id' => $this->campaign_id,
                'since' => ArrayHelper::getValue($insights, 'date_start'),
                'until' => ArrayHelper::getValue($insights, 'date_stop'),
            ]);
    
            if (!$theCampaignActivity) {
                $theCampaignActivity = new CampaignActivity();
            }

            $theCampaignActivity->attributes = (array) $insights;
            $theCampaignActivity->user_id = $this->created_by;
            $theCampaignActivity->campaign_id = $this->campaign_id;
            $theCampaignActivity->since = ArrayHelper::getValue($insights, 'date_start');
            $theCampaignActivity->until = ArrayHelper::getValue($insights, 'date_stop');
            $theCampaignActivity->raw_data = Json::encode($insights);
            $theCampaignActivity->save();
        }

    }

    public function getLeads() {
        $adArray = $this->toArray();
        $lead_form_id = null;
        try {
            // Example of an ad with lead gen form
            // {
            //     "name": "New Lead generation Ad",
            //     "creative": {
            //         "object_story_spec": {
            //             "page_id": "111884090581240",
            //             "instagram_actor_id": "3001794573232284",
            //             "link_data": {
            //                 "link": "http://fb.me/",
            //                 "message": "Primary text. New.",
            //                 "name": "Headline. New.",
            //                 "attachment_style": "link",
            //                 "description": "Description. Optional. New",
            //                 "image_hash": "d95750fa8a2e20683222e3cdb7c3e7dd",
            //                 "call_to_action": {
            //                     "type": "GET_OFFER",
            //                     "value": {
            //                         "lead_gen_form_id": "923266348398596"
            //                     }
            //                 }
            //             }
            //         },
            //         "id": "23848811439300656"
            //     }
            // }
            $lead_form_id = $adArray['creative']['object_story_spec']['link_data']['call_to_action']['value']['lead_gen_form_id'];
        } catch (\Throwable $th) {
            throw new BadRequestHttpException('Ad does not have lead gen form attached: ' . $th->getMessage());
        }

        $api = $this->getApiClient();
        $response = $api->call(
            "/{$lead_form_id}/leads", 'GET', ['fields' => 'created_time,id,ad_id,form_id,field_data']
          );
        $leads = $response->getContent();
        return $leads['data'];
    }

    public function getAdManagerUrl() {
        $ad_account_id = $this->ad_account_id;
        $business_id = ""; // optional?
        $ad_id = $this->ad_id;
        $url = "https://facebook.com/adsmanager/manage/ads/edit?act={$ad_account_id}&selected_ad_ids={$ad_id}";
        return $url;
    }

    public function getAdTitle() {
        if (empty($this->creative)) {
            return '';
        }

        $creative = json_decode($this->creative);
        return $creative->title ?? '';
    }

    public function getcreativeData() {
        return json_decode($this->creative);
    }

    public function getAdsetData() {
        return json_decode($this->adset);
    }
}
