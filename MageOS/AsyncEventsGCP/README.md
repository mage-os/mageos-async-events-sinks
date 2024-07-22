# MageOS Async Events GCP

GCP event sinks for [mageos-async-events](https://github.com/mage-os/mageos-async-events)

## Installation

```sh
composer require mage-os/mageos-async-events-aws
```

## GCP event sinks

### Google Pub/Sub

**Setup Application Default Credentials (ADC)**

A Service Account with the `Pub/Sub Publisher` role is required so that the notifier can relay events into Google
Pub/Sub.

Under `Stores -> Services -> Async Events GCP` set the `ADC Path`.

![gcp-pubsub-config example](docs/gcp-pubsub-config.png)

**Create a Pub/Sub Subscription**

The following is an example to create a Pub/Sub subscription for the `example.event`

```sh
curl --location --request POST 'https://test.mageos.dev/rest/V1/async_event' \
--header 'Authorization: Bearer TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
    "asyncEvent": {
        "event_name": "example.event",
        "recipient_url": "Google Pub/Sub Topic",
        "verification_token": "supersecret",
        "metadata": "pubsub"
    }
}'
```
