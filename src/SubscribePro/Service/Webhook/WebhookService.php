<?php

namespace SubscribePro\Service\Webhook;

use SubscribePro\Exception\HttpException;
use SubscribePro\Service\AbstractService;

/**
 * Config options for webhook service:
 * - instance_name
 *   Specified class must implement \SubscribePro\Service\Webhook\EventInterface interface
 *   Default value is \SubscribePro\Service\Webhook\Event
 *
 *   @see \SubscribePro\Service\Webhook\EventInterface
 * - instance_name_destination
 *   Specified class must implement \SubscribePro\Service\Webhook\Event\DestinationInterface interface
 *   Default value is \SubscribePro\Service\Webhook\Event\Destination
 *   @see \SubscribePro\Service\Webhook\Event\DestinationInterface
 * - instance_name_endpoint
 *   Specified class must implement \SubscribePro\Service\Webhook\Event\Destination\EndpointInterface interface
 *   Default value is \SubscribePro\Service\Webhook\Event\Destination\Endpoint
 *   @see \SubscribePro\Service\Webhook\Event\Destination\EndpointInterface
 *
 * @method \SubscribePro\Service\Webhook\EventInterface   retrieveItem($response, $entityName, \SubscribePro\Service\DataInterface $item = null)
 * @method \SubscribePro\Service\Webhook\EventInterface[] retrieveItems($response, $entitiesName)
 *
 * @property \SubscribePro\Service\Webhook\EventFactory $dataFactory
 */
class WebhookService extends AbstractService
{
    /**
     * Service name
     */
    public const NAME = 'webhook';

    public const API_NAME_WEBHOOK_EVENT = 'webhook_event';

    public const CONFIG_INSTANCE_NAME_DESTINATION = 'instance_name_destination';
    public const CONFIG_INSTANCE_NAME_ENDPOINT = 'instance_name_endpoint';

    /**
     * @return bool
     */
    public function ping()
    {
        try {
            $this->httpClient->post('/services/v2/webhook-test.json');
        } catch (HttpException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Read webhook event from request
     *
     * @return \SubscribePro\Service\Webhook\EventInterface|bool
     */
    public function readEvent()
    {
        $rawRequestBody = $this->httpClient->getRawRequest();
        $webhookEvent = !empty($rawRequestBody['webhook_event'])
            ? json_decode($rawRequestBody['webhook_event'], true)
            : false;

        return $webhookEvent ? $this->dataFactory->create($webhookEvent) : false;
    }

    /**
     * @param int $eventId
     *
     * @return \SubscribePro\Service\Webhook\EventInterface
     *
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadEvent($eventId)
    {
        $response = $this->httpClient->get("/services/v2/webhook-events/{$eventId}.json");

        return $this->retrieveItem($response, self::API_NAME_WEBHOOK_EVENT);
    }
}
