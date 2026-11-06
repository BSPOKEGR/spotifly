<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Spotifly Backend API',
    description: 'API for managing agricultural parcels, actions, alerts, and integrating with external services (FarmCalendar, Weather Service, Pest & Disease Management)',
)]
#[OA\Server(
    url: '/',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Laravel Sanctum token authentication'
)]
#[OA\Tag(
    name: 'Authentication',
    description: 'User authentication endpoints'
)]
#[OA\Tag(
    name: 'Parcels',
    description: 'Farm parcel management (proxied to FarmCalendar API)'
)]
#[OA\Tag(
    name: 'Actions',
    description: 'Farm activity/action management (proxied to FarmCalendar API)'
)]
#[OA\Tag(
    name: 'Alerts',
    description: 'Generated alerts from pest and disease risk calculations'
)]
#[OA\Tag(
    name: 'Weather',
    description: 'Weather forecast data (proxied to Weather Service API)'
)]
#[OA\Tag(
    name: 'Insect Data',
    description: 'Pest and insect monitoring data (proxied to Pest & Disease API)'
)]
#[OA\Tag(
    name: 'KPIs',
    description: 'Key Performance Indicators aggregated from multiple services'
)]
class OpenApiController extends Controller
{
    // This controller exists only to hold OpenAPI annotations
}
