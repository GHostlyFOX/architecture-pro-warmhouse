package services

import (
	"context"
	"encoding/json"
	"log"
	"sync"
	"time"

	"github.com/segmentio/kafka-go"
)

// TemperatureData represents the structure of temperature data
type TemperatureData struct {
	DeviceID    int       `json:"device_id"`
	Temperature float64   `json:"temp"`
	Timestamp   time.Time `json:"timestamp"`
}

// TemperatureStorage stores the latest temperature for each sensor
type TemperatureStorage struct {
	mu           sync.RWMutex
	temperatures map[int]TemperatureData
}

// NewTemperatureStorage creates a new TemperatureStorage
func NewTemperatureStorage() *TemperatureStorage {
	return &TemperatureStorage{
		temperatures: make(map[int]TemperatureData),
	}
}

// GetTemperature returns the latest temperature for a given device ID
func (s *TemperatureStorage) GetTemperature(deviceID int) (TemperatureData, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	temp, ok := s.temperatures[deviceID]
	return temp, ok
}

// SetTemperature sets the latest temperature for a given device ID
func (s *TemperatureStorage) SetTemperature(deviceID int, temp float64) {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.temperatures[deviceID] = TemperatureData{
		DeviceID:    deviceID,
		Temperature: temp,
		Timestamp:   time.Now(),
	}
}

// KafkaConsumer consumes temperature data from a Kafka topic
type KafkaConsumer struct {
	reader  *kafka.Reader
	storage *TemperatureStorage
}

// NewKafkaConsumer creates a new KafkaConsumer
func NewKafkaConsumer(brokers []string, topic string, storage *TemperatureStorage) *KafkaConsumer {
	r := kafka.NewReader(kafka.ReaderConfig{
		Brokers: brokers,
		Topic:   topic,
		GroupID: "smarthome-monolith",
	})
	return &KafkaConsumer{
		reader:  r,
		storage: storage,
	}
}

// Run starts the Kafka consumer
func (c *KafkaConsumer) Run(ctx context.Context) {
	go func() {
		for {
			m, err := c.reader.ReadMessage(ctx)
			if err != nil {
				log.Printf("Error reading message from Kafka: %v", err)
				continue
			}

			var data struct {
				DeviceID int     `json:"device_id"`
				Temp     float64 `json:"temp"`
			}
			if err := json.Unmarshal(m.Value, &data); err != nil {
				log.Printf("Error unmarshalling message from Kafka: %v", err)
				continue
			}

			c.storage.SetTemperature(data.DeviceID, data.Temp)
			log.Printf("Received temperature from Kafka: device_id=%d, temp=%.2f", data.DeviceID, data.Temp)
		}
	}()
}
