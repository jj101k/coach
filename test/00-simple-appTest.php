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
        $bus = new \Minibus\App();
        $bus->addService(new \MinibusTest\LocalService());
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
            $written,
            "Hello World",
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
            $written,
            "Bonjour world",
            "Unfiltered endpoints are unchanged"
        );
    }
}