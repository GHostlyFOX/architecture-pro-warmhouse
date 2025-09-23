package models

import "time"

// Switcher represents a switcher device in the system
type Switcher struct {
	ID        int       `json:"id"`
	Name      string    `json:"name"`
	UserID    int       `json:"user_id"`
	CreatedAt time.Time `json:"created_at"`
}

// SwitcherCreate represents the data needed to create a new switcher
type SwitcherCreate struct {
	Name   string `json:"name" binding:"required"`
	UserID int    `json:"user_id" binding:"required"`
}
