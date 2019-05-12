<?php
namespace Minibus;
/**
 * This handles what happens for a specific version of a service. This must be
 * subclassed.
 */
abstract class ServiceVersion {
    /**
     * @property \Minibus\Service
     */
    protected $service;

    /**
     * Builds the object
     *
     * @param \Minibus\Service $service
     */
    public function __construct(\Minibus\Service $service) {
        $this->service = $service;
    }

    /**
     * Attaches all the endpoints to the given app
     *
     * @param \Celery\App $app
     */
    abstract public function attach(\Celery\App $app);

    /**
     * Attaches the fallback endpoint handler to the given app
     *
     * @param \Celery\App $app
     */
    public function attachFallback(\Celery\App $app) {
        $app->any("/{path:.*}", function($request, $response, $args) {
            return $this->service->proxyRequest($request);
        });
    }
}