<?php
namespace MinibusTest;
/**
 * This connects to the local service!
 */
class LocalService extends \Minibus\Service {
    /**
     * @property \Celery\App|null
     */
    private $app;

    /**
     * Builds the object
     */
    public function __construct() {
        require_once "test/lib/LocalService/Master.php";
        $this->versions["master"] = new \MinibusTest\LocalService\Master($this);
    }
    /**
     * @inheritdoc
     */
    public function attach(\Celery\App $app) {
        $this->app = $app;
        return parent::attach($app);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string {
        return "local";
    }

    /**
     * @inheritdoc
     */
    public function proxyRequest(
        \Psr\Http\Message\ServerRequestInterface $request
    ): \Psr\Http\Message\ResponseInterface {
        return $this->app->handleRequest($request->withUri(
            $request->getUri()->withPath(
                preg_replace("#^/[^/]+/[^/]+#", "", $request->getUri()->getPath())
            )
        ));
    }
}