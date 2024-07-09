# MageOS Async Events AWS

AWS destinations for [mageos-async-events](https://github.com/mage-os/mageos-async-events)

## Installation

```sh
composer require mage-os/mageos-async-events-aws
```

## Supported AWS event sinks

* EventBridge: send events to an Amazon EventBridge bus
* SQS: send events to an AWS SQS queue

### Configure AWS Credentials

An IAM role with the `events:PutEvents` action is required so that the notifier can relay events into Amazon
EventBridge.

Under `Stores -> Services -> Async Events AWS` set the `Access Key ID` and the `Secret Access Key` and the `Region`. You
can also choose to configure the source of the event.

![AWS Config](./docs/config.png)

### Create a Subscription

The following is an example to create an EventBridge subscription for the `example.event` 
```shell
curl --location --request POST 'https://test.mageos.dev/rest/V1/async_event' \
--header 'Authorization: Bearer TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
    "asyncEvent": {
        "event_name": "example.event",
        "recipient_url": "Amazon Event Bridge ARN",
        "verification_token": "supersecret",
        "metadata": "eventbridge"
    }
}'
```

## Contributing

This is a repository for distribution only.
Contributions are welcome on the development repository [mageos-async-events-sinks](https://github.com/mage-os/mageos-async-events-sinks)
