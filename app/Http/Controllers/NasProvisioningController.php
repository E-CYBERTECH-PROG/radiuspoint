<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Response;

class NasProvisioningController extends Controller
{
    /**
     * Public, unauthenticated (but public_token-scoped) endpoint a router's own bootstrap
     * script fetches and imports — see Router::buildProvisioningScript() and
     * RouterController::provision(). Generated fresh on every request so it always reflects
     * this router's current credentials, never hand-copied into a visible script.
     */
    public function startup(Router $router): Response
    {
        return response($router->buildProvisioningScript(), 200)
            ->header('Content-Type', 'text/plain');
    }
}
