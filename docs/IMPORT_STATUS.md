# Import Status - Session Resume Document

**Last Updated:** 2026-01-30

Use this document to resume import work after breaks.

---

## Current State

### ✅ Downloaded (17 artists with full metadata)

| Artist | Collection ID | Shows | Tracks Imported |
|--------|---------------|-------|-----------------|
| moe. | moe | 1,803 | 34,420 |
| Grateful Dead | GratefulDead | 1,861 | 26 |
| Keller Williams | KellerWilliams | 955 | ? |
| Leftover Salmon | LeftoverSalmon | 883 | ? |
| Guster | Guster | 759 | ? |
| Cabinet | Cabinet | 615 | ? |
| Of a Revolution | OfARevolution | 541 | ? |
| Phil Lesh and Friends | PhilLeshandFriends | 487 | ? |
| Lettuce | Lettuce | 417 | ? |
| My Morning Jacket | MyMorningJacket | 414 | ? |
| God Street Wine | GodStreetWine | 371 | ? |
| Grace Potter | GracePotterandtheNocturnals | 362 | ? |
| Matisyahu | Matisyahu | 344 | ? |
| Billy Strings | BillyStrings | 326 | ? |
| Goose | GooseBand | 286 | ? |
| Furthur | Furthur | 248 | ? |
| Dogs in a Pile | DogsInAPile | 135 | ? |

**Total:** ~10,807 shows downloaded

### ❌ Still Need Downloading (18 artists)

| Artist | Collection ID | Priority |
|--------|---------------|----------|
| Phish | Phish | HIGH |
| STS9 | STS9 | HIGH |
| Widespread Panic | WidespreadPanic | HIGH |
| String Cheese Incident | TheStringCheeseIncident | HIGH |
| Disco Biscuits | TheDiscoBiscuits | MEDIUM |
| Railroad Earth | RailroadEarth | MEDIUM |
| Tedeschi Trucks Band | TedeschiTrucksBand | MEDIUM |
| Yonder Mountain String Band | YonderMountainStringBand | MEDIUM |
| Twiddle | Twiddle | MEDIUM |
| Ween | Ween | MEDIUM |
| Ratdog | Ratdog | MEDIUM |
| Rusted Root | RustedRoot | LOW |
| Tea Leaf Green | TeaLeafGreen | LOW |
| Warren Zevon | WarrenZevon | LOW |
| King Gizzard | KingGizzardAndTheLizardWizard | LOW |
| John Mayer | JohnMayer | LOW |
| Smashing Pumpkins | SmashingPumpkins | LOW |
| Mac Creek | MacCreek | LOW |

---

## Resume Commands

### Step 1: Start Docker (if not running)
```bash
open -a Docker
sleep 10
bin/start
```

### Step 2: Run populate with --force on downloaded artists
```bash
# Force re-populate all downloaded artists
for artist in "Dogs in a Pile" "Furthur" "Goose" "Billy Strings" "Matisyahu" \
              "Grace Potter and the Nocturnals" "God Street Wine" "Lettuce" \
              "My Morning Jacket" "Phil Lesh and Friends" "Of a Revolution" \
              "Cabinet" "Guster" "Leftover Salmon" "Keller Williams" "moe." \
              "Grateful Dead"; do
    echo "Populating: $artist"
    bin/magento archive:populate "$artist" --force
done
```

### Step 3: Download remaining artists
```bash
# Download HIGH priority artists (large collections)
bin/magento archive:download "Phish"
bin/magento archive:download "STS9"
bin/magento archive:download "Widespread Panic"
bin/magento archive:download "String Cheese Incident"

# Then populate them
bin/magento archive:populate "Phish"
bin/magento archive:populate "STS9"
bin/magento archive:populate "Widespread Panic"
bin/magento archive:populate "String Cheese Incident"
```

### Step 4: Run remaining artists
```bash
# Medium priority
for artist in "Disco Biscuits" "Railroad Earth" "Tedeschi Trucks Band" \
              "Yonder Mountain String Band" "Twiddle" "Ween" "Ratdog"; do
    bin/magento archive:download "$artist"
    bin/magento archive:populate "$artist"
done

# Low priority
for artist in "Rusted Root" "Tea Leaf Green" "Warren Zevon" \
              "King Gizzard and the Lizard Wizard" "John Mayer" \
              "Smashing Pumpkins" "Mac Creek"; do
    bin/magento archive:download "$artist"
    bin/magento archive:populate "$artist"
done
```

### Final: Reindex
```bash
bin/magento indexer:reindex
bin/fix-index  # Fix catalog_category_product_index_store1
```

---

## Quick Status Commands

```bash
# Check what's downloaded
docker compose exec -T phpfpm bash -c "
for dir in /var/www/html/var/archivedotorg/metadata/*/; do
    name=\$(basename \"\$dir\")
    count=\$(find \"\$dir\" -name '*.json' 2>/dev/null | wc -l)
    if [ \$count -gt 10 ]; then echo \"\$name: \$count shows\"; fi
done | sort"

# Check import history
bin/mysql -e "SELECT artist_name, command_name, status, items_successful
FROM archivedotorg_import_run ORDER BY created_at DESC LIMIT 20;"

# Check artist stats
bin/mysql -e "SELECT artist_name, downloaded_shows, imported_tracks, match_rate_percent
FROM archivedotorg_artist_status WHERE imported_tracks > 0 ORDER BY imported_tracks DESC;"
```

---

## Two-Step Import Process

Each artist requires TWO steps:

1. **Download** - Fetches metadata from Archive.org to local cache
   ```bash
   bin/magento archive:download "Artist Name"
   ```
   - Creates JSON files in `var/archivedotorg/metadata/{CollectionId}/`
   - Takes 5-30 minutes per artist depending on collection size

2. **Populate** - Creates Magento products from cached metadata
   ```bash
   bin/magento archive:populate "Artist Name"
   ```
   - Matches tracks using hybrid algorithm (exact → alias → phonetic → fuzzy)
   - Creates products in Magento with track data
   - Takes 1-10 minutes per artist

**Note:** Download only needs to happen once. Populate can be re-run with `--force`.

---

## See Also

- `docs/INITIAL_SITE_SETUP.md` - Full setup documentation
- `docs/COMMAND_GUIDE.md` - All CLI commands
- `CLAUDE.md` - Project overview and architecture
