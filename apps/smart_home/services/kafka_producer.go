package services

import (
	"context"
	"encoding/json"

	"github.com/segmentio/kafka-go"
)

// KafkaProducer produces messages to a Kafka topic
type KafkaProducer struct {
	writer *kafka.Writer
}

// NewKafkaProducer creates a new KafkaProducer
func NewKafkaProducer(brokers []string) *KafkaProducer {
	w := &kafka.Writer{
		Addr:         kafka.TCP(brokers...),
		Balancer:     &kafka.LeastBytes{},
		RequiredAcks: kafka.RequireOne,
		Async:        true,
		Completion: func(messages []kafka.Message, err error) {
			if err != nil {
				// log.Printf("failed to write messages: %v", err)
			}
		},
	}
	return &KafkaProducer{writer: w}
}

// SendMessage sends a message to a Kafka topic
func (p *KafkaProducer) SendMessage(topic string, message interface{}) error {
	jsonMessage, err := json.Marshal(message)
	if err != nil {
		return err
	}

	return p.writer.WriteMessages(context.Background(),
		kafka.Message{
			Topic: topic,
			Value: jsonMessage,
		},
	)
}

// Close closes the Kafka writer
func (p *KafkaProducer) Close() error {
	return p.writer.Close()
}
