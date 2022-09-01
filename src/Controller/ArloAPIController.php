<?php

namespace Drupal\arlo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class ArloAPIController.
 */
class ArloAPIController extends ControllerBase {

  /**
   * Service endpoint function. Handles the submission from the Arlo
   * webhook.
   */
  public function endpoint(Request $request) {
    // Get the input from our posted data. If no data was posted, then we can
    // bail on the operation.
    $content = $request->getContent();
    $data = json_decode($content, FALSE);

    // What we get from the API is metadata only. For "Event" resource types, the
    // resourceID is the event id. This needs to then be used to fetch the event
    // information from Arlo and either create a new event or update an existing
    // one as it exists in Drupal.

    if (!empty($data)) {
      
      // Handle event events.
      if (!empty($data->events)) {
        foreach ($data->events as $event) {
          switch ($event->type) {
            case 'Event.Created':
              $fetchedEvent = $this->fetchEvent($event->resourceId);
              if (!empty($fetchedEvent)) {
                $this->createEvent($fetchEvent);
              }
              break;
            case 'Event.Updated':
              $fetchedEvent = $this->fetchEvent($event->resourceId);
              if (!empty($fetchedEvent)) {
                $this->updateEvents($fetchEvent);
              }
              else {
                // TODO: Not sure yet if we should delete the reference if there is no data
                // or handle the error differently. Right now we will mark this issue
                // as to be done.
              }
              break;
            default:
              break;
          }
        }
      }
      $response = [
        'data' => 'Success',
        'method' => 'GET'
      ];
    }
    else {
      file_put_contents(__DIR__ . '/endpoint.json', 'test');
      $response = [
        'data' => 'Failure',
        'method' => 'GET'
      ];
    }

    // I do not think this is necessary.
    // header('Access-Control-Allow-Origin: *');

    return new JsonResponse($response);
  }

  public function fetchEvent($eventID = NULL) {
    /**
     * Sample URL structure to fetch the data we need from an event. A full
     * list of fields can be found at:
     *
     * https://developer.arlo.co/doc/api/2012-02-01/pub/resources/eventsearch
     * 
     * Note that if no eventID is passed, it will return all events which will
     * then need to be iterated through - so always be prepared for > 1 result.
     */
    $config = $this->config('arlo.settings');
    $platform_id = $config->get('platform_id');
    $filter = (!empty($eventID)) ? '&filter=eventID=' . $eventID : NULL;
    $url = 'https://' . $platform_id . '/api/2012-02-01/pub/resources/eventsearch?fields=description,summary,sessionsdescription,presenters,viewuri' . $filter;
    $client = \Drupal::httpClient();
    try {
      $request = $client->get($url);
      $status = $request->getStatusCode();
      $response = $request->getBody()->getContents();
      $data = json_decode($response);
      if (!empty($data->Items)) {
        return $data->Items;
      }
      else {
        return [];
      }
    }
    catch (RequestException $e) {
      // TODO: Handle the error.
    }
  }

  public function createEvent($payload) {

  }

  public function updateEvents($payload) {

  }

}
