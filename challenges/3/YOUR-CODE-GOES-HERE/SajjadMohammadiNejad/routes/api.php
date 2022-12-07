<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\TravelSpotController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// If a group of routes all utilize the same controller, you may use the controller method to define the common controller for all of the routes within the group.
Route::controller(AuthController::class)->group(function () {
    Route::post("/register", "register");
    Route::post("/login",  "login");
});

Route::middleware("auth:sanctum")->group(function (Router $router) {
    $router->get("/user", [AuthController::class, "user"]);

    $router->post("/travels", [TravelController::class, "store"]);
    $router->get("/travels/{travel}", [TravelController::class, "view"]);
    $router->post("/travels/{travel}/take", [TravelController::class, "take"]);
    $router->post("/travels/{travel}/cancel", [TravelController::class, "cancel"]);
    $router->post("/travels/{travel}/passenger-on-board", [TravelController::class, "passengerOnBoard"]);
    $router->post("/travels/{travel}/done", [TravelController::class, "done"]);

    $router->post("/travels/{travel}/spots", [TravelSpotController::class, "store"]);
    $router->get("/travels/{travel}/spots/{spot}", [TravelSpotController::class, "view"]);
    $router->delete("/travels/{travel}/spots/{spot}", [TravelSpotController::class, "destroy"]);
    $router->post("/travels/{travel}/spots/{spot}/arrived", [TravelSpotController::class, "arrived"]);

    $router->get("/driver", [DriverController::class, "view"]);
    $router->post("/driver", [DriverController::class, "signup"]);
    $router->put("/driver", [DriverController::class, "update"]);
});
