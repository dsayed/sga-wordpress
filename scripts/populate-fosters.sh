#!/usr/bin/env bash
set -euo pipefail

# Populate foster dogs from the real SGA website (savinggreatanimals.org)
# Usage: docker compose run --rm -v $(pwd)/scripts:/scripts --entrypoint sh wpcli /scripts/populate-fosters.sh

WP="wp --allow-root --path=/var/www/html/web/wp"

create_foster() {
  local title="$1" breed="$2" age="$3" status="$4" notes="$5" image_slug="${6:-}"
  ID=$($WP post create --post_type=foster_dog --post_title="$title" --post_status=publish --porcelain)
  $WP post meta update "$ID" foster_dog_breed "$breed"
  $WP post meta update "$ID" foster_dog_age "$age"
  $WP post meta update "$ID" foster_dog_urgency "$status"
  $WP post meta update "$ID" foster_dog_notes "$notes"

  # Attach featured image if slug provided (matches an existing media attachment)
  if [ -n "$image_slug" ]; then
    ATTACH_ID=$($WP post list --post_type=attachment --name="$image_slug" --field=ID --format=ids 2>/dev/null) || true
    if [ -n "$ATTACH_ID" ]; then
      $WP post meta update "$ID" _thumbnail_id "$ATTACH_ID"
      echo "  Created: $title (ID: $ID, Status: $status, Photo: $image_slug)"
    else
      echo "  Created: $title (ID: $ID, Status: $status, Photo: NOT FOUND for $image_slug)"
    fi
  else
    echo "  Created: $title (ID: $ID, Status: $status)"
  fi
}

echo "=== Populating Foster Dogs ==="

create_foster \
  "Binky" \
  "Rat Terrier" \
  "2 years" \
  "urgent" \
  "Sweet but timid. ~25 lbs. Walks nicely on leash. Best in quiet residential area, home with yard preferred. No cats. Timid around other dogs. Has a heart murmur requiring cardiology care." \
  "binky"

create_foster \
  "Aiden" \
  "Jindo" \
  "2 years" \
  "urgent" \
  "Sweet boy but wary of strangers. 35-40 lbs. Needs experienced foster. Needs time to trust. Leash trained and crate trained." \
  "aiden"

create_foster \
  "Miss Piggy" \
  "Lab mix" \
  "12 years" \
  "needed" \
  "Owner is elderly and no longer able to care for her. Has lived outside for years. She is friendly." \
  "miss-piggy"

create_foster \
  "Roxy" \
  "Mixed breed" \
  "2.5 years" \
  "needed" \
  "Sweet but worried about meeting people. Nervous in unfamiliar settings. Can get along with other dogs but hit and miss. Needs quiet home with fenced yard and experienced handler. NOT suitable for homes with children." \
  "roxy"

create_foster \
  "Chihuahua Mix Puppies (Litter of 4)" \
  "Chihuahua mix" \
  "9 weeks" \
  "needed" \
  "2 male, 2 female. Arriving February 24. Would like to see them fostered in pairs." \
  "chi-pups"

create_foster \
  "Cattle Dog Mix Puppy 1" \
  "Cattle dog mix" \
  "6-7 weeks" \
  "needed" \
  "Female. One of three cattle dog mix puppies." \
  "cattle-dog-pup-1"

create_foster \
  "Cattle Dog Mix Puppy 2" \
  "Cattle dog mix" \
  "6-7 weeks" \
  "needed" \
  "Female. One of three cattle dog mix puppies." \
  "cattle-dog-pup-2"

create_foster \
  "Cattle Dog Mix Puppy 3" \
  "Cattle dog mix" \
  "6-7 weeks" \
  "secured" \
  "Female. Foster secured." \
  "cattle-dog-pup-1"

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

echo "=== Foster Dogs populated (10 created) ==="
