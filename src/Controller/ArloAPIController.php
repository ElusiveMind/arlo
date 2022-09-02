<?php

namespace Drupal\arlo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
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
                $this->updateEvents($fetchedEvent);
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
      $response = [
        'data' => 'Failure',
        'method' => 'GET'
      ];
    }

    // I do not think this is necessary.
    // header('Access-Control-Allow-Origin: *');

    return new JsonResponse($response);
  }

  /**
   * Get a specific event from the Arlo API and return the result.
   * 
   * @param int eventID
   * The EventID (or sometimes known as resourceID) of the event that we wish to
   * get from the Arlo API. If left blank wil return all events.
   * 
   * @return array $items
   * An array of returned items. A single item if the eventID is specified or all
   * of the events if it is not.
   */
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
    $filter = NULL; //(!empty($eventID)) ? '&filter=eventID=' . $eventID : NULL;
    $url = 'https://' . $platform_id . '/api/2012-02-01/pub/resources/eventsearch?fields=eventid,name,description,summary,sessionsdescription,presenters,viewuri' . $filter;
    try {
      $request = \Drupal::httpClient()->get($url, [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]);
      $status = $request->getStatusCode();
      $response = $request->getBody();
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

  /**
   * 
   * Update an existing event when new information is presented via the update
   * webhook.
   * 
   * @param array $payload
   * The payload array of objects that comes back from the Arlo API.
   * 
   * @return array $status
   * Returns an array with the count of SAVED_NEW and SAVED_UPDATED counts.
   */
  public function createEvent($payload) {
    $status = [
      SAVED_NEW => 0,
      SAVED_UPDATED => 0,
    ];
    foreach ($payload as $key => $event) {
      $node = Node::create(['type' => 'arlo_event']);
      $node->set('title', htmlspecialchars($event->Name));
      $node->set('field_arlo_link', $event->ViewUri);
      $node->set('field_arlo_summary', $event->Summary);
      $node->set('field_arlo_event_id', $event->EventID);
      $node->set('body', $event->Description->Text);
      $node->enforceIsNew();
      $return = $node->save();
      $status[$return]++;
    }
    return $status;
  }

  /**
   * Update an existing event when new information is presented via the update
   * webhook.
   * 
   * @param array $payload
   * The payload array of objects that comes back from the Arlo API.
   * 
   * @return array $status
   * Returns an array with the count of SAVED_NEW and SAVED_UPDATED counts.
   */
  public function updateEvents($payload) {
    $status = [
      SAVED_NEW => 0,
      SAVED_UPDATED => 0,
    ];
    foreach ($payload as $key => $event) {
      // We need to determine from the EventID if this is a new entry or
      // an update to an existing one.
      $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->condition('field_arlo_event_id', $event->EventID)
        ->condition('type', 'arlo_event')
        ->accessCheck(TRUE)
        ->execute();
      if (!empty($nids)) {
        if (count($nids) > 1) {
          // Now this would be a problem if we have more than one node of a 
          // particular event. If this is the case, we need to remove all of
          // the existing nodes and save a new one.
          //
          // This is a failsafe for duplicate arlo_event nodes.
          \Drupal::logger('arlo')->notice('Arlo EventID ' . $event->EventID . ' has more than one node. Destroying existing nodes and creating a single new one.');
          $handler = \Drupal::entityTypeManager()->getStorage("node");
          $entities = $handler->loadMultiple($nids);
          $handler->delete($entities);
          $status = $this->createEvent(array($event));
        }
        else {
          // Yes, we only have one nid, but it comes in an array, so iterate
          // to make it easy.
          foreach ($nids as $nid) {
            $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
            $node->set('title', htmlspecialchars($event->Name));
            $node->set('field_arlo_link', $event->ViewUri);
            $node->set('field_arlo_summary', $event->Summary);
            $node->set('field_arlo_event_id', $event->EventID);
            $node->set('body', $event->Description->Text);
            $node->save();
          }
        }
      }
      else {
        $node = Node::create(['type' => 'arlo_event']);
        $node->set('title', htmlspecialchars($event->Name));
        $node->set('field_arlo_link', $event->ViewUri);
        $node->set('field_arlo_summary', $event->Summary);
        $node->set('field_arlo_event_id', $event->EventID);
        $node->set('body', $event->Description->Text);
        $node->enforceIsNew();
        $return = $node->save();
        $status[$return]++;
      }
    }
    return $status;
  }
}
