#!/usr/bin/env bash
set -euo pipefail

# Populate foster dogs from the real SGA website (savinggreatanimals.org)
# Usage: docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/populate-fosters.sh

WP="wp --allow-root --path=/var/www/html/web/wp"

create_foster() {
  local title="$1" breed="$2" age="$3" status="$4" notes="$5"
  ID=$($WP post create --post_type=foster_dog --post_title="$title" --post_status=publish --porcelain)
  $WP post meta update "$ID" foster_dog_breed "$breed"
  $WP post meta update "$ID" foster_dog_age "$age"
  $WP post meta update "$ID" foster_dog_urgency "$status"
  $WP post meta update "$ID" foster_dog_notes "$notes"
  echo "  Created: $title (ID: $ID, Status: $status)"
}

echo "=== Populating Foster Dogs ==="

create_foster \
  "Aiden" \
  "Jindo" \
  "2 years" \
  "urgent" \
  "Sweet boy but wary of strangers. 35-40 lbs. Needs experienced foster. Needs time to trust. Leash trained and crate trained."

create_foster \
  "Miss Piggy" \
  "Lab mix" \
  "12 years" \
  "needed" \
  "Owner is elderly and no longer able to care for her. Has lived outside for years. She is friendly."

create_foster \
  "Roxy" \
  "Mixed breed" \
  "2.5 years" \
  "needed" \
  "Sweet but worried about meeting people. Nervous in unfamiliar settings. Can get along with other dogs but hit and miss. Needs quiet home with fenced yard and experienced handler. NOT suitable for homes with children."

create_foster \
  "Chihuahua Mix Puppies (Litter of 4)" \
  "Chihuahua mix" \
  "9 weeks" \
  "needed" \
  "2 male, 2 female. Arriving February 24. Would like to see them fostered in pairs."

create_foster \
  "Cattle Dog Mix Puppy 1" \
  "Cattle dog mix" \
  "6-7 weeks" \
  "needed" \
  "Female. One of three cattle dog mix puppies."

create_foster \
  "Cattle Dog Mix Puppy 2" \
  "Cattle dog mix" \
  "6-7 weeks" \
  "needed" \
  "Female. One of three cattle dog mix puppies."

create_foster \
  "Cattle Dog Mix Puppy 3" \
  "Cattle dog mix" \
  "6-7 weeks" \
  "secured" \
  "Female. Foster secured."

create_foster \
  "Male Mixed Breed Puppy" \
  "Mixed breed" \
  "3 months" \
  "secured" \
  "Male. Friendly with other dogs. Foster secured."

create_foster \
  "Female Mixed Breed Puppy" \
  "Mixed breed" \
  "3 months" \
  "secured" \
  "Female. Friendly with other dogs. Foster secured."

echo "=== Foster Dogs populated (9 created) ==="
