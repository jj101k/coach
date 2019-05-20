<?php
require_once "vendor/autoload.php";
/**
 * This runs tests related to retention, addition, subtraction and modification
 * of headers.
 */
class HeaderTest extends \PHPUnit\Framework\TestCase {
    /**
     * @property string|null
     */
    private $forwarded = null;

    /**
     * @property array|null
     */
    private $headersSent = null;

    /**
     * All tests (capitalise filter)
     */
    public function test() {
        require_once "test/lib/LocalService.php";
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->getMock();

        $saved_greeting = null;
        $saved_headers = null;

        $app->method("sendHeaders")->will(
            $this->returnCallback(function(
                string $greeting,
                array $headers
            ) use (
                &$saved_greeting,
                &$saved_headers
            ) {
                $saved_greeting = $greeting;
                $saved_headers = $headers;
            })
        );
        $bus = $this
            ->getMockBuilder("\Minibus\App")
            ->setMethods(["getApp"])
            ->getMock();
        $bus->method("getApp")->willReturn($app);
        $bus->addService(new \MinibusTest\LocalService());

        $bus->getApp()->get("/hello/{name}", function($request, $response, $args) {
            $this->forwarded = $request->getHeaderLine("Forwarded");
            $response->getBody()->write("Hello {$args["name"]}");
            $response = $response->withHeader("X-Y-Z", "a-b-c");
            $this->headersSent = $response->getHeaders();
            return $response;
        });
        ob_start(function($buffer) {return "";});
        $bus->run(false, [
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => "/local/master/hello/world",
            "HTTP_FORWARDED" => "for=1.2.3.4",
            "REMOTE_ADDR" => "5.6.7.8",
        ]);
        ob_end_flush();
        $this->assertSame(
            $this->forwarded,
            "for=1.2.3.4, for=5.6.7.8",
            "Headers retained correctly on the way in"
        );
        $headers = [];
        foreach($this->headersSent as $name => $values) {
            foreach($values as $value) {
                $headers[] = "{$name}: {$value}";
            }
        }
        $this->assertSame(
            $headers,
            $saved_headers,
            "Headers retained correctly on the way out"
        );
    }
}