<?php
namespace Minibus;
/**
 * This handles what happens for a service. This just serves as a wrapper for
 * the given service versions.
 *
 * A service which does not have a specific version should be "master".
 */
abstract class Service {
    /**
     * @property array Version strings mapped to \Minibus\ServiceVersion objects
     */
    protected $versions = [];

    /**
     * Attaches all the endpoints to the given app
     *
     * @param \Celery\App $app
     */
    public function attach(\Celery\App $app) {
        foreach($this->versions as $version => $handler) {
            $app->group("/{$version}", function($group_app) use ($handler) {
                $handler->attach($group_app);
                $handler->attachFallback($group_app);
            });
        }
    }

    /**
     * Returns the name by which the service is known, eg. "foo". This will be
     * the first component of the path.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Handles the request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function proxyRequest(
        \Psr\Http\Message\ServerRequestInterface $request
    ): \Psr\Http\Message\ResponseInterface {
        throw new \Exception("Not implemented");
    }
}