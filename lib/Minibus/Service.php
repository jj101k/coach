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
     * @property bool If this is false, any certificates on HTTPS won't be
     *  verified (may be useful during testing).
     */
    protected $verifyTls = true;

    /**
     * @property array Version strings mapped to \Minibus\ServiceVersion objects
     */
    protected $versions = [];

    /**
     * Turns an input ServerRequest into an output Request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function forwardedRequest(
        \Psr\Http\Message\ServerRequestInterface $request
    ): \Psr\Http\Message\RequestInterface {
        $depth = $this->getAttachDepth() + 1;
        $root_uri = $this->getRootUri();

        $request = $request->withUri(
            $root_uri
                ->withPath(
                    preg_replace(
                        substr($root_uri->getPath(), -1) == "/" ?
                            "#^" . str_repeat("/[^/]+", $depth) . "/#" :
                            "#^" . str_repeat("/[^/]+", $depth) . "#",
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
        );
        $client_addr = @$request->getServerParams()["REMOTE_ADDR"];
        if($client_addr) {
            $request = $request->withAddedHeader(
                "Forwarded",
                preg_match("/:/", $client_addr) ?
                    "for=\"[{$client_addr}]\"" :
                    "for={$client_addr}"
            );
        }
        return $request
            ->withoutHeader("X-Forwarded-For")
            ->withoutHeader("X-Forwarded-Host")
            ->withoutHeader("X-Forwarded-Proto");
    }

    /**
     * Returns the depth at which this service is attached, eg. if it's on "/"
     * it's 0, or on "/foo/bar" it's 2. Usually this would be 1.
     *
     * @return int
     */
    abstract protected function getAttachDepth(): int;

    /**
     * Returns a URI object for the root of the service to proxy to. If this
     * includes a trailing slash, only URLs which have a / following the version
     * will be mapped.
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
        return \CurlPsr\Handler::run(
            $this->forwardedRequest($request),
            $this->verifyTls,
            10000
        )->withoutHeader("Transfer-Encoding");
    }
}
