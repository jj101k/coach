<?php
namespace CoachTest\LocalService;
/**
 * This adds the wrapping fake endpoint
 */
class Master extends \Coach\ServiceVersion {
    /**
     * Attaches all the endpoints to the given app
     *
     * @param \Celery\App $app
     */
    public function attach(\Celery\App $app) {
        $app->get("/hello/{name}", function($request, $response, $args) {
            $inner_response = $this->service->proxyRequest($request);
            $new_body = new \Celery\Body();
            $new_body->write(
                preg_replace_callback(
                    "/ (\w+)$/",
                    function($md) {
                        return " " . ucfirst(strtolower($md[1]));
                    },
                    $inner_response->getBody()
                )
            );
            return $inner_response->withBody($new_body);
        });
    }
}