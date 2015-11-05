<?php
namespace Nebo15\LumenMixpanel\Middleware;

use Closure;
use Nebo15\LumenMixpanel\Mixpanel;

class TerminableMiddleware
{
    private $mixpanel;

    public function __construct(Mixpanel $mixpanel)
    {
        $this->mixpanel = $mixpanel;
    }
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
    public function terminate($request, $response)
    {
        $this->mixpanel->sendCollectedEvents();
    }
}