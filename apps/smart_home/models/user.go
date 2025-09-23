package models

import "time"

// User represents a user in the system
type User struct {
	ID        int       `json:"id"`
	Username  string    `json:"username"`
	Token     string    `json:"-"` // Omit token from JSON responses
	CreatedAt time.Time `json:"created_at"`
}
