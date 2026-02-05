# MageOS Async Events Azure

Azure event sinks for [mageos-async-events](https://github.com/mage-os/mageos-async-events)

## Installation

```sh
composer require mage-os/mageos-async-events-azure
```

## Authentication

Microsoft Entra is used to authenticate with Azure services. An Azure Service
Principal with the `EventGrid Data Sender` role is required so that the notifier
can relay events into Azure Event Grid.

Configure OAuth parameters in the Magento admin panel.

Under `Stores -> Services -> Async Events Azure` set the `Tenant ID`,
`Client ID` and the `Client Secret`.

## Azure event sinks

### Azure Event Grid

This module provides an event sink for Azure Event Grid. This allows you to relay
events into Azure Event Grid, enabling you to integrate with other Azure
services or third-party applications that support Event Grid.

The Event Grid topic must use the CloudEvents schema. Events are sent in the
structured content mode of CloudEvents.

**Create an Event Grid Subscription**

The following is an example to create an Event Grid subscription for the `example.event`

```shell
curl --location --request POST 'https://test.mageos.dev/rest/V1/async_event' \
--header 'Authorization: Bearer TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
    "asyncEvent": {
        "event_name": "example.event",
        "recipient_url": "Event Grid Topic Endpoint",
        "verification_token": "supersecret", // not used by this sink but required by the API
        "metadata": "eventgrid"
    }
}'
```

## Contributing

This is a repository for distribution only.
Contributions are welcome on the development
repository [mageos-async-events-sinks](https://github.com/mage-os/mageos-async-events-sinks)
