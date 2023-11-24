<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
 */

//=========================Api Version 1 start=============================//
//=================Login /Registration api=================//		
Route::prefix('v2')->namespace('Api\V2')->group(function () {
    Route::post('signup', 'UserController@registration');
    Route::post('login', 'UserController@login');
    Route::middleware('auth:api', 'isActiveToken')->group(function () {
        Route::post('signup-phase-two', 'UserController@registrationPhaseTwo');
         /*-------------Instagram -----------------------------*/
        Route::get('instagram/login', 'InstagramController@auth')->name('instagram-login');
        Route::post('instagram/callback', 'InstagramController@callback');
    });

    Route::get('email-verification/{id}/{hash}', 'UserController@emailVerify');

    Route::get('social/login/{provider}', 'socialiteController@redirectToProvider');
    Route::get('social/login/{provider}/callback', 'socialiteController@handleProviderCallback');
    Route::get('social/login/{provider}/redirect', 'socialiteController@handleProviderRedirect');

   
   
    
});
Route::prefix('v1')->namespace('Api\V1')->group(function () {
    Route::get('public-profile/{channel}/{uuid?}', 'ProfileController@publicProfile');
    Route::post('login', 'UserController@login');
    Route::post('get-user-info', 'UserController@getUserInfo');
    Route::post('forgot-password', 'UserController@forgetPassword');
    Route::post('update-password', 'UserController@updatePassword')->name('update-password');
    Route::get('category-list', 'WebsiteSettingController@categoryList');
    Route::get('get-country', 'WebsiteSettingController@getCountry');
//    Route::get('campaign-detail/{uuid}./{infuluuid}', 'WebsiteSettingController@campaginDetail');
//    Route::get('campaign-approved/{uuid}', 'WebsiteSettingController@campaginApproved');
    Route::post('public-campaign-list', 'CampaignController@publicCampaignList');

    Route::get('social/login/{provider}', 'socialiteController@redirectToProvider');
    Route::get('social/login/{provider}/callback', 'socialiteController@handleProviderCallback');
    Route::get('social/login/{provider}/redirect', 'socialiteController@handleProviderRedirect');


    Route::post('registration', 'UserController@registration');
    Route::get('email-verification/{id}', 'UserController@emailVerify');
    Route::post('checkemail', 'UserController@CheckEmail');
   

    Route::post('save-contact', 'ContactController@saveContact');
    /* -Currency conersion----------*/
    Route::post('currency-convert', 'NoMiddlewareController@currencyConvert');


    Route::post('channel-list', 'NoMiddlewareController@channelList');
   
    Route::post('channel-auto-suggest', 'NoMiddlewareController@channelAutoSuggest');

    Route::get('unauthorized','NoMiddlewareController@unauthorized')->name('unauthorized');

    Route::middleware('auth:api', 'isActiveToken', 'isAuthorized')->group(function () {
        Route::post('user-dashboard', 'UserController@userDashboard')->name('user-dashboard');
        Route::group(['prefix' => 'influencer'], function () {
            Route::middleware(['influencer'])->group(function () {


                Route::post('dashboard', 'UserController@dashboard')->name('influencer.dashboard');
                Route::post('update-profile', 'ProfileSettingController@updateProfile');
              
                /* Channel------------------------------ */
                Route::post('channel-list', 'ChannelController@channelList');
                Route::post('add-channel', 'ChannelController@addChannel');
                Route::post('edit-channel', 'ChannelController@editChannel');
                Route::post('delete-channel', 'ChannelController@deleteChannel');
                Route::post('view-channel', 'ChannelController@viewChannel');
                Route::post('verify-channel', 'ChannelController@verifyChannel');

                /* Apply on campagin----------------------- */
                Route::post('get-campaign', 'AppliedCampaignController@getCampaign');
                Route::post('view-campaign', 'AppliedCampaignController@viewCampaign');
                Route::post('applied-campaign', 'AppliedCampaignController@appliedCampaign');
                Route::post('edit-application', 'AppliedCampaignController@editApplication');

                Route::post('get-application', 'ApplicationController@getApplication');
                Route::post('view-application', 'ApplicationController@ViewApplication');
                Route::post('delete-application', 'ApplicationController@deleteApplication');
                Route::get('service-management', 'ServiceController@serviceManagment');
                Route::post('affiliate_program', 'ServiceController@affiliateProgram');
                Route::post('managment-service', 'ServiceController@managmentService');

                /* Invitation---------------------------------- */

                Route::post('invitation-list', 'InviteController@invitationList');
                /* Order---------------------------------- */
                Route::post('live-orders', 'OrderController@liveOrders');
                Route::post('view-order', 'OrderController@viewOrder');
                /*------------------Increase order deadline*/
                 Route::post('increase-deadline', 'OrderController@increaseDeadline');

                //Route::post('bank-detail', 'UserController@bankDetail');
            });
        });
        Route::group(['prefix' => 'brand'], function () {
            Route::middleware(['brand'])->group(function () {
                Route::post('dashboard', 'UserController@dashboard')->name('brand.dashboard');
                Route::post('dont_show_me_again', 'ProfileSettingController@dontShowMeAgain');

                /* Campaign------------------------------------ */
                Route::post('campaign-list', 'CampaignController@campaignList');
                Route::post('add-campaign', 'CampaignController@addCampaign');
                Route::post('edit-campaign', 'CampaignController@editCampaign');
                Route::post('view-campaign', 'CampaignController@viewCampaign');
                Route::post('delete-campaign', 'CampaignController@deleteCampaign');

                /* Application-------------------------------- */
                Route::post('application-responses', 'BrandApplicationController@applicationResponses');
                Route::post('application-view', 'BrandApplicationController@applicationView');
                Route::post('application-shortlist', 'BrandApplicationController@applicationShortlist');
                Route::post('application-reject', 'BrandApplicationController@applicationReject');

                /* Invitation---------------------------------- */
                Route::get('invite-campaign-list', 'InviteController@inviteCampaignList');
                Route::post('send-invitation', 'InviteController@sendInvitation');
                Route::post('invitation-list', 'InviteController@invitationList');
                Route::post('camp-invitations', 'InviteController@campInvitation');
                Route::post('invite-influencers', 'InviteController@inviteInfluencers');

                /* Order----------------------------------------- */
                Route::post('hire-influencer', 'OrderController@hireInfluencer');
                Route::post('live-orders', 'OrderController@liveOrders');
                Route::post('view-order', 'OrderController@viewOrder');
                //Route::get('complete-orders','OrderController@completeOrders');
                //Route::get('cancelled-orders','OrderController@cancelledOrders');
                Route::post('cancel-order', 'OrderController@cancelOrder');
                Route::post('order-manual-pay-invoice', 'OrderController@orderManualPayInvoice');

                /* list----------------------------------------- */
                Route::post('manage-list', 'ListController@manageList');
//                Route::get('list-channel/{id}', 'ListController@getListChannels');
                Route::post('create-list', 'ListController@createList');
                Route::post('edit-list', 'ListController@editList');
                Route::post('delete-list', 'ListController@deleteList');
                Route::post('view-list', 'ListController@viewList');
                Route::post('send-invite-list', 'InviteController@sendInviteList');

                Route::post('add-to-list', 'ListController@addTolist');
                Route::post('remove-to-list', 'ListController@removeTolist');

               

                /* Add money to wallet */
                Route::post('add-money-to-wallet', 'PaymentController@addMoneyTowalet');
                Route::post('save-transaction', 'PaymentController@saveTransaction');
                /* ------Favourites Channels------ */
                Route::post('like', 'FavouriteController@like');
                Route::post('unlike', 'FavouriteController@unlike');
                Route::post('liked', 'FavouriteController@liked');

                Route::post('channel-list', 'SearchController@channelList');
                Route::post('recommended-influencers', 'SearchController@recommendedInfluencers');
                /* ------Revealed Channels------ */
                Route::post('revealedchannels', 'RevealController@revealedchannels');
                Route::post('revealed-channel-list', 'RevealController@revealedchannelList');
                Route::post('saved-channel-list', 'RevealController@savedchannelList');
                /* -------Subscriptions */


                Route::get('subscription/get', 'SubscriptionsController@getSubscription');
                Route::post('subscription/subscribe', 'SubscriptionsController@SubscribePay');
                Route::post('subscription/response', 'SubscriptionsController@SubscribePayResponse');

                Route::post('subscription/topup', 'SubscriptionsController@TopupPay');
                Route::post('subscription/topupresponse', 'SubscriptionsController@TopupPayResponse');

                Route::post('upgrade-plan', 'UpgradePlanController@upgradePlan');
                Route::post('upgrade-response', 'UpgradePlanController@upgradeResponse');

                /* ---- Update category prrfrence------------- */
                Route::post('update-category-preference', 'ProfileSettingController@updateCategoryPreference');

                Route::post('influencers-graph', 'GraphController@influencersGraph');
                Route::post('whatsapp-request', 'InviteController@whatsappRequest');
                Route::post('download-invoice', 'InviteController@InvoiceDownload');
               
            });
        });

        /* Common Api for both brand/Influencer */

        Route::post('show-similar', 'NoMiddlewareController@showSimilar');
        Route::post('channel-list-add', 'NoMiddlewareController@channelListAdd');
        Route::post('add-profile-to-list', 'NoMiddlewareController@addProfileToList');

        /* -----Order Processs----------------------------- */
        Route::post('order-process', 'OrderProcessController@orderProcess');
        Route::post('pay-remaining-order-amount', 'OrderProcessController@payRemainingOrderAmount');
        Route::get('transaction-history', 'OrderController@transactionHistory');
        Route::get('order-history', 'OrderController@orderHistory');

        /* Pay  Amount Using Paymnet gateway */
        Route::post('order-payment', 'PayOrderAmountController@orderPayment');
        Route::post('save-order-payment', 'PayOrderAmountController@saveOrderPayment');

          /* Pay Remaining Amount Using Paymnet gateway */
        Route::post('pay-order-amount', 'PayOrderAmountController@payOrderAmount');
        Route::post('save-order-transaction', 'PayOrderAmountController@saveOrderTransaction');

        /* Message ---------------------------------- */
        Route::get('all-users', 'MessageController@allUsers');
        Route::post('new-chat', 'MessageController@newChat');
        //Route::post('view-message', 'MessageController@ViewMessage');
        //Route::post('send-message', 'MessageController@sendMessage');
        /*------Help--------------------------------*/
        Route::post('help-msg', 'MessageController@helpMsg');
        /* Dispute ---------------------------------- */

        Route::post('raise-dispute', 'DisputeController@raiseDispute');
        Route::post('dispute-list', 'DisputeController@disputeList');

        /* Profile Setting ---------------------------------- */
        Route::post('/logout', 'UserController@logout');
        Route::get('get-profile', 'UserController@getProfile');
        Route::post('/change-password', 'UserController@changePassword');
        Route::post('/change-email', 'UserController@changeEmail');
        Route::post('profile-setting', 'ProfileSettingController@ProfileSetting');

        Route::get('menu-count', 'UserController@menuCount');
        Route::get('notifications', 'NotificationController@notifications');
        Route::get('read-all', 'NotificationController@readAll');
        Route::post('read-user-message', 'NotificationController@readUserMessage');
    });
});

