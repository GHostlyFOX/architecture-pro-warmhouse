package handlers

import (
	"net/http"

	"smarthome/db"
	"smarthome/models"
	"smarthome/services"

	"github.com/gin-gonic/gin"
)

// SwitcherHandler handles switcher-related requests
type SwitcherHandler struct {
	DB            *db.DB
	KafkaProducer *services.KafkaProducer
}

// NewSwitcherHandler creates a new SwitcherHandler
func NewSwitcherHandler(db *db.DB, kafkaProducer *services.KafkaProducer) *SwitcherHandler {
	return &SwitcherHandler{
		DB:            db,
		KafkaProducer: kafkaProducer,
	}
}

// RegisterRoutes registers the switcher routes
func (h *SwitcherHandler) RegisterRoutes(router *gin.RouterGroup) {
	switchers := router.Group("/switchers")
	{
		switchers.POST("", h.CreateSwitcher)
	}
}

// CreateSwitcher handles POST /api/v1/switchers
func (h *SwitcherHandler) CreateSwitcher(c *gin.Context) {
	var switcherCreate models.SwitcherCreate
	if err := c.ShouldBindJSON(&switcherCreate); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Create switcher in the database
	switcher, err := h.DB.CreateSwitcher(c.Request.Context(), switcherCreate)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to create switcher"})
		return
	}

	// Publish message to Kafka
	message := map[string]interface{}{
		"device_type": "switcher",
		"device_id":   switcher.ID,
		"user_id":     switcher.UserID,
	}
	if err := h.KafkaProducer.SendMessage("new_device", message); err != nil {
		// Log the error, but don't fail the request
		// The switcher is already created in the DB
		// We might need a more robust outbox pattern here in a real system
		// but for now, we just log the error.
		// log.Printf("Failed to send message to Kafka: %v", err)
	}

	c.JSON(http.StatusCreated, switcher)
}
