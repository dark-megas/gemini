<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XFrameOptionsMiddleware
{
    /**
     * Maneja una solicitud entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Eliminar el encabezado X-Frame-Options si existe
        $response->headers->remove('X-Frame-Options');

        // Permitir iframes solo desde dominios específicos
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' https://gemini-master-fdzbjb.laravel.cloud/");

        return $response;
    }
}
