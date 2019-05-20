<?php
namespace Minibus;
/**
 * The main bus object. This will set up linkage to all the service objects.
 */
class App {
    /**
     * @property \Celery\App
     */
    private $app;

    /**
     * A default index page for the service. This may be overridden to provide more human-friendly guidance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function indexPage(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ): \Psr\Http\Message\ResponseInterface {
        $response->getBody()->write(
            "This is a web service, it should be used from properly configured client code only."
        );
        return $response->withHeader("Content-Type", "text/plain");
    }

    /**
     * Builds the object
     */
    public function __construct() {
        $this->app = new \Celery\App();
        $this->app->get("/", [$this, "indexPage"]);
    }

    /**
     * Adds the service, connecting it to the PSR-7 app
     *
     * @param \Minibus\Service $service
     * @return self
     */
    public function addService(\Minibus\Service $service): self {
        $this->getApp()->group("/{$service->getName()}", function($group_app) use ($service) {
            $service->attach($group_app);
        });
        return $this;
    }

    /**
     * Returns the app so that you can add any extra endpoints that may be required
     *
     * @return \Celery\App
     */
    public function getApp(): \Celery\App {
        return $this->app;
    }

    /**
     * Processes the request
     *
     * @param bool $silent
     * @param array|null $server_params
     */
    public function run(bool $silent = false, ?array $server_params = null) {
        $this->getApp()->run($silent, $server_params);
    }
}