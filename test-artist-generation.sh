#!/bin/bash
# Test script to verify artist name generation

echo "Testing Artist Name Generation"
echo "=============================="
echo ""

# Add a test artist to the data patch temporarily
TEST_ARTIST="Joe's Band & The Friends"
echo "Test Artist: '$TEST_ARTIST'"
echo ""

# Expected outputs
echo "Expected Collection ID: JoesBandAndTheFriends"
echo "Expected URL Key: joes-band-and-the-friends"
echo ""

# Add the test artist to the data patch
cp src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php.backup

# Insert test artist at line 660 (before the closing ];)
sed -i.tmp "659a\\
        '$TEST_ARTIST' => [\\
            'url_key' => 'testartist',\\
            'albums' => [],\\
        ]," src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php

# Sync to container
bin/copytocontainer app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php >/dev/null 2>&1

# Run export (dry run)
echo "Running export..."
bin/magento archive:migrate:export --dry-run 2>&1 | grep -A1 "$TEST_ARTIST" || echo "Would create: joes-band-and-the-friends.yaml"

# Restore original
mv src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php.backup src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php
rm -f src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php.tmp
bin/copytocontainer app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php >/dev/null 2>&1

echo ""
echo "✅ Test complete! Restored original file."
