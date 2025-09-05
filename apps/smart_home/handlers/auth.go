package handlers

import (
	"net/http"
	"strings"

	"smarthome/db"

	"github.com/gin-gonic/gin"
)

// AuthHandler handles authentication-related requests
type AuthHandler struct {
	DB *db.DB
}

// NewAuthHandler creates a new AuthHandler
func NewAuthHandler(db *db.DB) *AuthHandler {
	return &AuthHandler{DB: db}
}

// RegisterRoutes registers the auth routes
func (h *AuthHandler) RegisterRoutes(router *gin.RouterGroup) {
	router.GET("/me", h.GetMe)
}

// GetMe handles GET /api/v1/me
// It expects a Bearer token in the Authorization header
func (h *AuthHandler) GetMe(c *gin.Context) {
	authHeader := c.GetHeader("Authorization")
	if authHeader == "" {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Authorization header is required"})
		return
	}

	parts := strings.Split(authHeader, " ")
	if len(parts) != 2 || parts[0] != "Bearer" {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid Authorization header format"})
		return
	}
	token := parts[1]

	user, err := h.DB.GetUserByToken(c.Request.Context(), token)
	if err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid token"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"user_id": user.ID})
}
