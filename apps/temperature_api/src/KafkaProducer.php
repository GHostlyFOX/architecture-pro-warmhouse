<?php

namespace App;

use RdKafka\Conf;
use RdKafka\Producer;

class KafkaProducer
{
    private Producer $producer;
    private string $topic;

    public function __construct(string $brokers, string $topic)
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $brokers);

        $this->producer = new Producer($conf);
        $this->topic = $topic;
    }

    public function sendMessage(string $message): void
    {
        $topic = $this->producer->newTopic($this->topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
        $this->producer->flush(1000);
    }
}
