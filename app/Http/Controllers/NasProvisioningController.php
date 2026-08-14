<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Response;

class NasProvisioningController extends Controller
{
    /**
     * Public, public_token-scoped endpoint that a router's bootstrap script fetches and imports.
     * See Router::buildProvisioningScript() and RouterController::provision(). Generated fresh
     * on every request so it always reflects current credentials.
     */
    public function startup(Router $router): Response
    {
        return response($router->buildProvisioningScript(), 200)
            ->header('Content-Type', 'text/plain');
    }
}
