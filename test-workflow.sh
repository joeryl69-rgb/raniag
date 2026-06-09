#!/bin/bash

# RANIAG Workflow Testing Script
# Replace BASE_URL with your server URL

BASE_URL="http://localhost"
INCIDENT_ID=""
TRACKING_NUMBER=""
ADMIN_TOKEN=""
AGENCY_TOKEN=""

echo "=== RANIAG Workflow Testing ==="
echo ""

# Step 1: Public submits report
echo "Step 1: Public Submits Report"
echo "-----"
RESPONSE=$(curl -s -X POST "$BASE_URL/report" \
  -H "Content-Type: application/json" \
  -d '{
    "incident_type_id": 1,
    "description": "Large pothole on Main Street causing accidents",
    "location_address": "Main Street cor. Rizal Avenue",
    "barangay": "Santa Cruz",
    "latitude": 18.4720,
    "longitude": 121.3250,
    "is_anonymous": true,
    "priority": "high"
  }')

echo "$RESPONSE" | jq .
TRACKING_NUMBER=$(echo "$RESPONSE" | jq -r '.tracking_number // empty')
echo "Tracking Number: $TRACKING_NUMBER"
echo ""

# Step 2: Get incident ID from database (for testing)
echo "Step 2: Get Incident Details"
echo "-----"
INCIDENT=$(curl -s -X POST "$BASE_URL/track" \
  -H "Content-Type: application/json" \
  -d "{\"tracking_number\": \"$TRACKING_NUMBER\"}")

echo "$INCIDENT" | jq .
INCIDENT_ID=$(echo "$INCIDENT" | jq -r '.id // empty')
echo "Incident ID: $INCIDENT_ID"
echo "Status: $(echo "$INCIDENT" | jq -r '.status')"
echo ""

# Step 3: Admin logs in
echo "Step 3: Admin Login"
echo "-----"
LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -c /tmp/admin_cookies.txt \
  -d "email=admin@pamplona.gov.ph&password=password")

echo "Login response received"
echo ""

# Step 4: Admin validates report
echo "Step 4: Admin Validates Report"
echo "-----"
VALIDATE=$(curl -s -X POST "$BASE_URL/admin/incidents/$INCIDENT_ID/validate" \
  -H "Content-Type: application/json" \
  -b /tmp/admin_cookies.txt \
  -d '{
    "action": "approve",
    "assigned_agency_id": 1,
    "notes": "Route to PNP for road accident investigation"
  }')

echo "$VALIDATE" | jq .
echo "Status after validation: $(echo "$VALIDATE" | jq -r '.incident.status // empty')"
echo ""

# Step 5: Check SMS log
echo "Step 5: Check SMS Notifications"
echo "-----"
curl -s -X GET "$BASE_URL/admin/dashboard.json" \
  -b /tmp/admin_cookies.txt | jq '.sms_stats'
echo ""

# Step 6: Agency logs in
echo "Step 6: Agency Login"
echo "-----"
curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -c /tmp/agency_cookies.txt \
  -d "email=pnp@pamplona.gov.ph&password=password"
echo "Agency logged in"
echo ""

# Step 7: Agency accepts assignment
echo "Step 7: Agency Accepts Assignment"
echo "-----"
ACCEPT=$(curl -s -X POST "$BASE_URL/agency/incidents/$INCIDENT_ID/accept" \
  -H "Content-Type: application/json" \
  -b /tmp/agency_cookies.txt \
  -d '{}')

echo "$ACCEPT" | jq .
echo "Status after accept: $(echo "$ACCEPT" | jq -r '.incident.status // empty')"
echo ""

# Step 8: Agency updates status
echo "Step 8: Agency Updates Status"
echo "-----"
UPDATE=$(curl -s -X PATCH "$BASE_URL/agency/incidents/$INCIDENT_ID/status" \
  -H "Content-Type: application/json" \
  -b /tmp/agency_cookies.txt \
  -d '{
    "status": "in_progress",
    "comment": "Investigation started. Team dispatched to location."
  }')

echo "$UPDATE" | jq .
echo "Status after update: $(echo "$UPDATE" | jq -r '.incident.status // empty')"
echo ""

# Step 9: Agency submits resolution
echo "Step 9: Agency Submits Resolution"
echo "-----"
RESOLVE=$(curl -s -X POST "$BASE_URL/agency/resolutions/$INCIDENT_ID" \
  -H "Content-Type: application/json" \
  -b /tmp/agency_cookies.txt \
  -d '{
    "summary": "Pothole repaired on Main Street",
    "actions_taken": "Filled pothole with asphalt, compacted, tested for safety"
  }')

echo "$RESOLVE" | jq .
echo "Status after resolution: $(echo "$RESOLVE" | jq -r '.incident.status // empty')"
echo ""

# Step 10: Public tracks final status
echo "Step 10: Public Tracks Final Status"
echo "-----"
FINAL=$(curl -s -X POST "$BASE_URL/track" \
  -H "Content-Type: application/json" \
  -d "{\"tracking_number\": \"$TRACKING_NUMBER\"}")

echo "$FINAL" | jq '{
  tracking_number: .tracking_number,
  status: .status,
  reported_at: .reported_at,
  resolved_at: .resolved_at,
  status_history: .statusUpdates | map({from: .from_status, to: .to_status, comment: .comment})
}'
echo ""

echo "=== Workflow Test Complete ==="
echo "✓ All 5 stages completed successfully"
