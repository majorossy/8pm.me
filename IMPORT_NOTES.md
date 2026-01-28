# 8PM Archive.org Import Notes

Quick reference for resuming imports.

## Last Session
- **Last imported:** Lettuce (8,465 tracks)
- **Date:** 2026-01-28

## Quick Commands

### Check Current Status
```bash
bin/magento archive:status
```

### Import a New Artist (2 steps)

**Step 1: Download metadata** (optional but recommended for large collections)
```bash
bin/magento archive:download:metadata --collection=ArtistCollection
```

**Step 2: Import shows**
```bash
# Dry run first
bin/magento archive:import:shows "Artist Name" --collection=ArtistCollection --dry-run --limit=10

# Then import (start small)
bin/magento archive:import:shows "Artist Name" --collection=ArtistCollection --limit=50

# Or import all
bin/magento archive:import:shows "Artist Name" --collection=ArtistCollection
```

### Other Useful Commands
```bash
# Refresh stats (rating, downloads, etc.)
bin/magento archive:refresh:products "Artist Name"

# Populate track categories
bin/magento archive:populate:tracks "Artist Name" --collection=ArtistCollection

# Cleanup old imports
bin/magento archive:cleanup:products --collection=ArtistCollection --dry-run
```

## Available Collections

| Collection ID | Artist Name | Pattern |
|---------------|-------------|---------|
| `BillyStrings` | Billy Strings | billystrings |
| `DiscoBiscuits` | Disco Biscuits | db |
| `Furthur` | Furthur | furthur |
| `Goose` | Goose | goose |
| `GratefulDead` | Grateful Dead | gd |
| `JRAD` | Joe Russo's Almost Dead | jrad |
| `KellerWilliams` | Keller Williams | kw |
| `LeftoverSalmon` | Leftover Salmon | los |
| `moe` | moe. | moe |
| `MyMorningJacket` | My Morning Jacket | mmj |
| `PhilLeshandFriends` | Phil Lesh & Friends | plf |
| `Phish` | Phish | phish |
| `RailroadEarth` | Railroad Earth | rre |
| `Ratdog` | Ratdog | ratdog |
| `StringCheeseIncident` | String Cheese Incident | sci |
| `STS9` | STS9 | sts9 |
| `TeaLeafGreen` | Tea Leaf Green | tlg |
| `TedeschiTrucksBand` | Tedeschi Trucks Band | ttb |
| `Twiddle` | Twiddle | twiddle |
| `UmphreysMcGee` | Umphrey's McGee | um |
| `WidespreadPanic` | Widespread Panic | wsp |
| `YonderMountainStringBand` | Yonder Mountain String Band | ymsb |

## Import History

| Date | Artist | Tracks | Notes |
|------|--------|--------|-------|
| 2026-01-28 | Lettuce | 8,465 | Full import (703 shows) |
| 2026-01-28 | God Street Wine | 8,636 | Full import (519 shows) |
| 2026-01-28 | Matisyahu | 5,911 | Full import (433 shows, 15 errors) |
| 2026-01-28 | Rusted Root | 3,117 | Full import (274 shows) |
| 2026-01-28 | Warren Zevon | 3,597 | Full import (214 shows) |
| 2026-01-28 | Dogs in a Pile | 1,974 | Full import (178 shows) |
| 2026-01-28 | Goose | 9,642 | Full import (767 shows) |
| 2026-01-27 | Twiddle | 1,829 | Full import |

## Tips

- Start with `--limit=50` to test before doing full imports
- Large collections (GratefulDead, Phish) have thousands of shows
- Use `--dry-run` to preview what will happen
- Check `var/log/archivedotorg.log` for detailed logs

## Full Documentation

See `src/app/code/ArchiveDotOrg/Core/CLAUDE.md` for complete technical docs.
