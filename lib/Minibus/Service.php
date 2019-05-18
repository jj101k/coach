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
     * Returns the depth at which this service is attached, eg. if it's on "/"
     * it's 0, or on "/foo/bar" it's 2. Usually this would be 1.
     *
     * @return int
     */
    abstract protected function getAttachDepth(): int;

    /**
     * Returns a URI object for the root of the service to proxy to. This must
     * include a trailing slash.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    abstract protected function getRootUri(): \Psr\Http\Message\UriInterface;

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
        $depth = $this->getAttachDepth() + 1;
        $root_uri = $this->getRootUri();
        return \CurlPsr\Handler::run(
            $request->withUri(
                $root_uri
                    ->withPath(
                        preg_replace(
                            "#^" . str_repeat("/[^/]+", $depth) . "/#",
                            $root_uri->getPath(),
                            $request->getUri()->getPath()
                        )
                    )
                    ->withFragment(
                        $request->getUri()->getFragment()
                    )
                    ->withQuery(
                        $request->getUri()->getQuery()
                    )
            ),
            true,
            10000
        );
    }
}