<?php
namespace Nebo15\LumenMixpanel;

use Laravel\Lumen\Application;
use Illuminate\Support\ServiceProvider;
use Nebo15\LumenMixpanel\Exceptions\LumenIntercomException;

class MixpanelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('Nebo15\LumenMixpanel\Mixpanel', function (Application $app) {
            $token = env('MIXPANEL_TOKEN', false);
            if (!$token) {
                throw new LumenMixpanelException('set intercom keys for LumenIntercom');
            }
            return Mixpanel::getInstance($token);
        });
    }
}
