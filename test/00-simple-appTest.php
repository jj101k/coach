<?php
require_once "vendor/autoload.php";
/**
 * This operates a simple app to test the functionality at a high level.
 */
class SimpleAppTest extends \PHPUnit\Framework\TestCase {
    /**
     * All tests (capitalise filter)
     */
    public function test() {
        require_once "test/lib/LocalService.php";
        $app = $this
            ->getMockBuilder("\Celery\App")
            ->setMethods(["sendHeaders"])
            ->getMock();

        $app->method("sendHeaders")->will(
            $this->returnCallback(function() {})
        );
        $bus = $this
            ->getMockBuilder("\Coach\App")
            ->setMethods(["getApp"])
            ->getMock();
        $bus->method("getApp")->willReturn($app);

        $bus->addService(new \CoachTest\LocalService());
        $bus->getApp()->get("/hello/{name}", function($request, $response, $args) {
            $response->getBody()->write("Hello {$args["name"]}");
            return $response;
        });
        $bus->getApp()->get("/bonjour/{name}", function($request, $response, $args) {
            $response->getBody()->write("Bonjour {$args["name"]}");
            return $response;
        });
        $written = "";
        ob_start(function($buffer) use (&$written) {
            $written .= $buffer;
            return "";
        });
        $bus->run(false, [
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => "/local/master/hello/world"
        ]);
        ob_end_flush();
        $this->assertSame(
            "Hello World",
            $written,
            "Capitalise filter ran correctly"
        );
        $written = "";
        ob_start(function($buffer) use (&$written) {
            $written .= $buffer;
            return "";
        });
        $bus->run(false, [
            "REQUEST_METHOD" => "GET",
            "REQUEST_URI" => "/local/master/bonjour/world"
        ]);
        ob_end_flush();
        $this->assertSame(
            "Bonjour world",
            $written,
            "Unfiltered endpoints are unchanged"
        );
    }
}