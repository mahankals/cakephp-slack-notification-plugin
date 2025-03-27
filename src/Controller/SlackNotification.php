<?php


declare(strict_types=1);


namespace CakePHPSlackNotification\Controller;


use Cake\Log\Log;
use Cake\Http\Client;
use Cake\Core\Configure;
use App\Controller\AppController as BaseController;


class SlackNotification extends BaseController
{
  protected Client $http;


  public function __construct()
  {
    $this->http = new Client();
  }


  public function send(array $body)
  {
    $fullurl = Configure::read('Notification.slack.webhook_url');


    $headers = [
      'Content-type' => 'application/json'
    ];


    try {
      // Send request
      $response = $this->http->post($fullurl, $body, ['headers' => $headers]);


      // Check HTTP status and handle non-successful responses
      $this->handleHttpError($response);


      // Log and return on success
      Log::write('debug', "Response Body: " . json_encode(json_decode($response->getStringBody()), JSON_PRETTY_PRINT));
      return ['success' => true, 'message' => $response->getStringBody(), 'error' => null];
    } catch (\Exception $e) {
      return ['success' => false, 'message' => null, 'error' => $e->getMessage()];
    }
  }


  private function handleHttpError($response): void
  {
    $status = $response->getStatusCode();


    if (!$response->isOk()) { // Checks if status is not in 200-299 range
      $responseBody = $response->getStringBody();
      // Check if response body is JSON, then format it nicely; otherwise, log as plain text
      $formattedBody = $this->formatResponseBody($responseBody);
      $errorMessage = "HTTP Error $status: " . $formattedBody;


      // Optional: Custom message for specific status codes
      switch ($status) {
        // case 403:
        //   throw new \Exception("Forbidden (403): Invalid token or insufficient permissions.");
        // case 404:
        //   throw new \Exception("Not Found (404): The requested resource doesn't exist.");
        default:
          throw new \Exception($errorMessage); // Generic error message
      }
    }
  }


  private function formatResponseBody($body): string
  {
    // Try to decode as JSON and pretty print if valid, otherwise return the raw string
    $decoded = json_decode($body);
    return $decoded !== null ? json_encode($decoded, JSON_PRETTY_PRINT) : $body;
  }
}
