<?php

use App\Domain\Admin\AdminServiceProvider;
use App\Domain\Audit\AuditServiceProvider;
use App\Domain\Auth\AuthServiceProvider;
use App\Domain\Bases\BasesServiceProvider;
use App\Domain\Clans\ClansServiceProvider;
use App\Domain\CocIntegration\CocIntegrationServiceProvider;
use App\Domain\GameAssets\GameAssetsServiceProvider;
use App\Domain\Marketplace\MarketplaceServiceProvider;
use App\Domain\Media\MediaServiceProvider;
use App\Domain\Messaging\MessagingServiceProvider;
use App\Domain\Moderation\ModerationServiceProvider;
use App\Domain\Notifications\NotificationsServiceProvider;
use App\Domain\PlayerAccounts\PlayerAccountsServiceProvider;
use App\Domain\Recruitment\RecruitmentServiceProvider;
use App\Domain\Search\SearchServiceProvider;
use App\Domain\Users\UsersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    UsersServiceProvider::class,
    CocIntegrationServiceProvider::class,
    PlayerAccountsServiceProvider::class,
    ClansServiceProvider::class,
    BasesServiceProvider::class,
    RecruitmentServiceProvider::class,
    MarketplaceServiceProvider::class,
    MessagingServiceProvider::class,
    MediaServiceProvider::class,
    GameAssetsServiceProvider::class,
    NotificationsServiceProvider::class,
    ModerationServiceProvider::class,
    AuditServiceProvider::class,
    SearchServiceProvider::class,
    AdminServiceProvider::class,
];
