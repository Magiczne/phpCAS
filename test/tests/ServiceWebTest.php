<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../harness/DummyRequest.php';
require_once dirname(__FILE__).'/../harness/BasicResponse.php';

/**
 * Test class for verifying the operation of service tickets.
 *
 *
 * Generated by PHPUnit on 2010-09-07 at 13:33:53.
 */
class ServiceWebTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CASClient
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
//     	phpCAS::setDebug(dirname(__FILE__).'/../test.log');
// 		error_reporting(E_ALL);

		$_SERVER['SERVER_NAME'] = 'www.clientapp.com';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['SERVER_ADMIN'] = 'root@localhost';
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SESSION = array();

		$this->object = new CASClient(
			CAS_VERSION_2_0, 	// Server Version
			true, 				// Proxy
			'cas.example.edu',	// Server Hostname
			443,				// Server port
			'/cas/',			// Server URI
			false				// Start Session
		);
		
		$this->object->setRequestImplementation('CAS_TestHarness_DummyRequest');
		$this->object->setCasServerCACert('/path/to/ca_cert.crt');
		$this->object->setNoExitOnAuthError();
		
		// Bypass PGT storage since CASClient->callback() will exit. Just build up the session manually
		// so that we are in a state from which we can attempt to fetch proxy tickets and make proxied requests.
		$_SESSION['phpCAS']['user'] = 'jdoe';
		$_SESSION['phpCAS']['pgt'] = 'PGT-clientapp-abc123';
		$_SESSION['phpCAS']['proxies'] = array();
		$_SESSION['phpCAS']['service_cookies'] = array();
		$_SESSION['phpCAS']['attributes'] = array();
		
		// Force Authentication to initialize the client.
		$this->object->forceAuthentication();

		/*********************************************************
		 * Enumerate our responses
		 *********************************************************/
		
		
		/*********************************************************
		 * 1. Valid Proxy ticket and service
		 *********************************************************/
		 
		// Proxy ticket Response
		$response = new CAS_TestHarness_BasicResponse('https', 'cas.example.edu', '/cas/proxy');
		$response->matchQueryParameters(array(
			'targetService' => 'http://www.service.com/my_webservice',
			'pgt' => 'PGT-clientapp-abc123',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/html;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:proxySuccess>
        <cas:proxyTicket>PT-asdfas-dfasgww2323radf3</cas:proxyTicket>
    </cas:proxySuccess>
</cas:serviceResponse>
");
		$response->ensureCaCertPathEquals('/path/to/ca_cert.crt');
		CAS_TestHarness_DummyRequest::addResponse($response);
		
		// Valid Service Response
		$response = new CAS_TestHarness_BasicResponse('http', 'www.service.com', '/my_webservice');
		$response->matchQueryParameters(array(
			'ticket' => 'PT-asdfas-dfasgww2323radf3',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/plain;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody("Hello from the service.");
		CAS_TestHarness_DummyRequest::addResponse($response);		
		
		
		/*********************************************************
		 * 2. Proxy Ticket Error
		 *********************************************************/
		 
		// Error Proxy ticket Response
		$response = new CAS_TestHarness_BasicResponse('https', 'cas.example.edu', '/cas/proxy');
		$response->matchQueryParameters(array(
			'targetService' => 'http://www.service.com/my_other_webservice',
			'pgt' => 'PGT-clientapp-abc123',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/html;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:proxyFailure code='INTERNAL_ERROR'>
        an internal error occurred during ticket validation
    </cas:proxyFailure>
</cas:serviceResponse>
");
		
		$response->ensureCaCertPathEquals('/path/to/ca_cert.crt');
		CAS_TestHarness_DummyRequest::addResponse($response);
		
		/*********************************************************
		 * 3. Server that doesn't respond/exist (sending failure)
		 *********************************************************/
		
		// Proxy ticket Response
		$response = new CAS_TestHarness_BasicResponse('https', 'cas.example.edu', '/cas/proxy');
		$response->matchQueryParameters(array(
			'targetService' => 'ssh://me.example.net',
			'pgt' => 'PGT-clientapp-abc123',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/html;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:proxySuccess>
        <cas:proxyTicket>PT-ssh-1234abce</cas:proxyTicket>
    </cas:proxySuccess>
</cas:serviceResponse>
");
		$response->ensureCaCertPathEquals('/path/to/ca_cert.crt');
		CAS_TestHarness_DummyRequest::addResponse($response);
		
		/*********************************************************
		 * 4. Service With Error status.
		 *********************************************************/
		
		// Proxy ticket Response
		$response = new CAS_TestHarness_BasicResponse('https', 'cas.example.edu', '/cas/proxy');
		$response->matchQueryParameters(array(
			'targetService' => 'http://www.service.com/my_webservice_that_has_problems',
			'pgt' => 'PGT-clientapp-abc123',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 200 OK',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/html;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody(
"<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    <cas:proxySuccess>
        <cas:proxyTicket>PT-12345-abscasdfasdf</cas:proxyTicket>
    </cas:proxySuccess>
</cas:serviceResponse>
");
		$response->ensureCaCertPathEquals('/path/to/ca_cert.crt');
		CAS_TestHarness_DummyRequest::addResponse($response);
		
		// Service Error Response
		$response = new CAS_TestHarness_BasicResponse('http', 'www.service.com', '/my_webservice_that_has_problems');
		$response->matchQueryParameters(array(
			'ticket' => 'PT-12345-abscasdfasdf',
		));
		$response->setResponseHeaders(array(
			'HTTP/1.1 500 INTERNAL SERVER ERROR',
			'Date: Wed, 29 Sep 2010 19:20:57 GMT',
			'Server: Apache-Coyote/1.1',
			'Pragma: no-cache',
			'Expires: Thu, 01 Jan 1970 00:00:00 GMT',
			'Cache-Control: no-cache, no-store',
			'Content-Type: text/plain;charset=UTF-8',
			'Content-Language: en-US',
			'Via: 1.1 cas.example.edu',
			'Connection: close',
			'Transfer-Encoding: chunked',
		));
		$response->setResponseBody("Problems have Occurred.");
		CAS_TestHarness_DummyRequest::addResponse($response);		
		
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
		CAS_TestHarness_DummyRequest::clearResponses();
    }

    /**
     * Test that we can at least retrieve a proxy-ticket for the service.
     */
    public function test_retrievePT() {
		$pt = $this->object->retrievePT('http://www.service.com/my_webservice', $err_code, $err_msg);
		$this->assertEquals('PT-asdfas-dfasgww2323radf3', $pt);
    }
	
	/**
     * Test that we can at least retrieve a proxy-ticket for the service.
     */
    public function test_serviceWeb() {
		$result = $this->object->serviceWeb('http://www.service.com/my_webservice', $err_code, $output);
		$this->assertTrue($result, $output);
		$this->assertEquals(PHPCAS_SERVICE_OK, $err_code);
		$this->assertEquals("Hello from the service.", $output);
    }
    
    /**
     * Verify that proxy-ticket Exceptions are caught and converted to error codes in serviceWeb().
     */
    public function test_serviceWeb_pt_error() {
		$result = $this->object->serviceWeb('http://www.service.com/my_other_webservice', $err_code, $output);
		$this->assertFalse($result, "serviceWeb() should have returned false on a PT error.");
		$this->assertEquals(PHPCAS_SERVICE_PT_FAILURE, $err_code);
		$this->assertStringStartsWith("PT retrieving failed", $output);
    }
    
    /**
     * Direct usage of the Proxied GET service.
     */
    public function test_http_get() {
    	$service = $this->object->getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
    	$service->setUrl('http://www.service.com/my_webservice');
    	$this->assertTrue($service->send(), 'Sending should have succeeded.');
    	$this->assertEquals(200, $service->getResponseStatusCode());
    	$this->assertEquals("Hello from the service.", $service->getResponseBody());
    }
    
    /**
     * Verify that a CAS_ProxyTicketException is thrown if we try to access a service
     * that results in a proxy-ticket failure.
     *
     * @expectedException CAS_ProxyTicketException
     */
    public function test_pt_exception() {
    	$service = $this->object->getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
    	$service->setUrl('http://www.service.com/my_other_webservice');
    	$this->assertFalse($service->send(), 'Sending should have failed');
    }
    
    /**
     * Verify that sending fails if we try to access a service
     * that has a valid proxy ticket, but where the service has a sending error.
     */
    public function test_http_get_service_failure() {
    	$service = $this->object->getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
    	$service->setUrl('ssh://me.example.net');
    	$this->assertFalse($service->send(), 'Sending should have failed');
    	$this->assertGreaterThan(0, strlen($service->getErrorMessage()));
    }
    
    /**
     * Verify that sending fails if we try to access a service
     * that has a valid proxy ticket, but where the service has a sending error.
     */
    public function test_http_get_service_500_error() {
    	$service = $this->object->getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
    	$service->setUrl('http://www.service.com/my_webservice_that_has_problems');
    	$this->assertTrue($service->send(), 'Sending should have been successful even though the response is an error response');
    	$this->assertEquals(500, $service->getResponseStatusCode());
    	$this->assertEquals("Problems have Occurred.", $service->getResponseBody());
    }
}
?>